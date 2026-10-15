<?php

namespace App\Services;

use App\Support\Barcodes;
use App\Support\PartNumber;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Supplier documents read from Infor LX:
 * - Identification cards (box labels), program YPU202B. The labels of a day are
 *   the open PO lines due that day (PDDTE), printed or not (YDOPC is not used).
 * - Delivery instruction (DI), programs YPU204B1 + YPU204B2. Open PO lines,
 *   firm planned orders (KFP) and customer forecasts (YKRE4, YM200, YM202, TM200).
 */
class SupplierDocuments
{
    private const ITEM_TYPES = "('G', 'P', 'Q', 'R')";

    // YPU202B: this vendor gets one label per PO line with the full quantity
    private const ONE_LABEL_PER_LINE_VENDOR = 101004;

    private string $lib;

    private string $custom;

    public function __construct(private int $vendor)
    {
        $this->lib = config('infor.library');
        $this->custom = config('infor.custom_library');
    }

    public function vendor(): int
    {
        return $this->vendor;
    }

    public function vendorName(): string
    {
        $row = $this->select("SELECT VNDNAM FROM {$this->lib}.AVM WHERE VENDOR = ?", [$this->vendor])->first();

        return $row['VNDNAM'] ?? '';
    }

    /**
     * What each day has, for the calendar.
     *
     * @return array<string, array{labels: int, di: int}> keyed by Y-m-d
     */
    public function calendarSummary(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $days = [];

        foreach ($this->labelLines($from, $to) as $line) {
            $date = $this->toDateString($line['PDDTE']);
            $days[$date] ??= ['labels' => 0, 'di' => 0];
            $days[$date]['labels'] += $this->boxCount($line['PQORD'], $line['IMBOXQ']);
        }

        foreach ($this->deliveryRows($from, $to) as $row) {
            $date = $this->toDateString($row['DDTE']);
            $days[$date] ??= ['labels' => 0, 'di' => 0];
            $days[$date]['di']++;
        }

        ksort($days);

        return $days;
    }

    /**
     * Identification cards for the PO lines due on a day, one per box (YPU202B).
     */
    public function labels(CarbonImmutable $date): Collection
    {
        $lines = $this->labelLines($date, $date);
        $nextProcess = $this->nextProcesses($lines->pluck('PPROD')->unique()->values()->all());
        $vendorName = $this->vendorName();

        return $lines->flatMap(function (array $line) use ($nextProcess, $vendorName) {
            $quantity = $this->number($line['PQORD']);
            $snp = $this->number($line['IMBOXQ']);
            $shift = substr(str_pad($line['POCNO'], 5), 4, 1);
            $due = $this->toDate($line['PDDTE']);

            if ($snp > 0) {
                $total = intdiv($quantity, $snp) + ($quantity % $snp ? 1 : 0);
                $remainder = $quantity % $snp;
            } else {
                $total = 1;
                $remainder = 0;
                $snp = $quantity;
            }

            // Vendor 101004 gets only the last label ("TTT-TTT") with the full quantity in the QR
            $firstBox = $this->vendor === self::ONE_LABEL_PER_LINE_VENDOR ? $total : 1;

            $labels = [];
            for ($box = $firstBox; $box <= $total; $box++) {
                $boxQty = ($box === $total && $remainder !== 0) ? $remainder : $snp;
                $qrQty = $this->vendor === self::ONE_LABEL_PER_LINE_VENDOR ? $quantity : $boxQty;
                $order = $this->pad($line['PORD'], 8).$this->pad($line['PLINE'], 4);

                $qr = '1'.$order
                    .$this->pad($box, 3).$this->pad($total, 3).$this->pad($qrQty, 6)
                    .str_pad(substr($line['PPROD'], 0, 35), 35)
                    .($due?->format('dmY') ?? '00000000')
                    .$shift;

                $labels[] = [
                    'delivery_date' => ($due?->format('d/m') ?? '').'-'.trim($shift),
                    'warehouse' => $line['PWHSE'],
                    'next_process' => $nextProcess[$line['PPROD']] ?? '',
                    'part' => PartNumber::format($line['PPROD'], $line['IREF04']),
                    'rl' => $line['IUFD14'] ?? '',
                    'pack' => $line['IMSPKT'],
                    'snp' => $boxQty,
                    'seq' => $this->pad($box, 3).'-'.$this->pad($total, 3),
                    'truck' => '',
                    'description' => $line['IDESC'],
                    'order' => $order,
                    'order_quantity' => $quantity,
                    'vendor' => $this->vendor,
                    'vendor_name' => $vendorName,
                    'qr' => Barcodes::qr($qr),
                    'barcode' => Barcodes::code39($order),
                ];
            }

            return $labels;
        });
    }

    /**
     * Delivery instruction issued on a day: everything due from that day on (YPU204B1/B2).
     */
    public function deliveryInstruction(CarbonImmutable $issued): Collection
    {
        return $this->deliveryRows($issued, null)
            ->map(fn (array $row) => [
                'part' => PartNumber::format($row['PROD'], $row['REF04']),
                'raw_part' => $row['PROD'],
                'snp' => $this->number($row['SNP']) ?: 1,
                'order' => $this->number($row['ORD']) ? $this->pad($row['ORD'], 8).$this->pad($row['LINE'], 4) : '',
                'origin' => $row['ORIGIN'],
                'date' => $this->toDate($row['DDTE'])?->format('d/m/y') ?? '',
                'sort_date' => (int) $row['DDTE'],
                'quantity' => $this->number($row['QTY']),
            ])
            ->map(fn (array $row) => $row + ['boxes' => max(1, (int) ceil($row['quantity'] / $row['snp']))])
            ->sortBy([['raw_part', 'asc'], ['sort_date', 'asc'], ['order', 'asc']])
            ->groupBy('raw_part');
    }

    /**
     * Open PO lines of the vendor due between two days (YPU202B selection).
     * Already printed lines (YDOPC) are included on purpose. IIU is limited to
     * its first record and the next process is read apart (nextProcesses):
     * joining IIU/FRT directly repeats each PO line once per record.
     */
    private function labelLines(CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        $sql = "
            SELECT P.PORD, P.PLINE, P.PPROD, P.PQORD, P.PDDTE, P.PWHSE, P.HSHOP, P.POCNO,
                   I.IDESC, I.IMBOXQ, I.IMSPKT, I.IREF04, U.IUFD14
            FROM {$this->lib}.HPO AS P
                     INNER JOIN {$this->lib}.IIM AS I ON I.IPROD = P.PPROD
                     LEFT JOIN {$this->lib}.IIU AS U ON U.IUPROD = P.PPROD AND U.IUSEQN = 1
            WHERE P.PVEND = ?
              AND P.PQORD <> 0
              AND I.IITYP IN ".self::ITEM_TYPES."
              AND P.PDDTE BETWEEN ? AND ?
            ORDER BY P.PDDTE, P.PPROD, P.PORD, P.PLINE";

        return $this->select($sql, [$this->vendor, (int) $from->format('Ymd'), (int) $to->format('Ymd')]);
    }

    /**
     * DI rows due between two days ($to = null: no upper limit).
     * Columns: ORIGIN, ORD, LINE, PROD, QTY, DDTE, SNP, REF04
     */
    private function deliveryRows(CarbonImmutable $from, ?CarbonImmutable $to): Collection
    {
        $from8 = (int) $from->format('Ymd');
        $to8 = $to ? (int) $to->format('Ymd') : 99999999;
        $types = self::ITEM_TYPES;
        $item = "I.IMPLC = ' ' AND I.IITYP IN {$types}";

        // Forecast files share the same layout with a different prefix
        $forecast = fn (string $file, string $p) => "
            SELECT 'FR', 0, 0, F.{$p}PATN, SUM(F.{$p}QUAN), F.{$p}DATE, I.IMBOXQ, I.IREF04
            FROM {$this->custom}.{$file} AS F
                     INNER JOIN {$this->lib}.IIM AS I ON I.IPROD = F.{$p}PATN
            WHERE I.IVEND = ? AND {$item}
              AND F.{$p}DATE BETWEEN ? AND ? AND F.{$p}QUAN <> 0
            GROUP BY F.{$p}PATN, F.{$p}DATE, I.IMBOXQ, I.IREF04";
        $mmvo = $forecast('YKRE4', 'K4');
        $mnaoTier1 = $forecast('YM200', 'M0');
        $mnaoTier2 = $forecast('YM202', 'M2');
        $toyota = $forecast('TM200', 'T2');

        $sql = "
            SELECT P.PID AS ORIGIN, P.PORD AS ORD, P.PLINE AS LINE, P.PPROD AS PROD, P.PQORD AS QTY,
                   P.PDDTE AS DDTE, I.IMBOXQ AS SNP, I.IREF04 AS REF04
            FROM {$this->lib}.HPO AS P
                     INNER JOIN {$this->lib}.IIM AS I ON I.IPROD = P.PPROD
            WHERE P.PVEND = ? AND P.PQORD <> 0 AND {$item}
              AND P.PDDTE BETWEEN ? AND ?

            UNION ALL
            SELECT 'FP', 0, 0, K.FPROD, K.FQTY, K.FRDTE, I.IMBOXQ, I.IREF04
            FROM {$this->lib}.KFP AS K
                     INNER JOIN {$this->lib}.IIM AS I ON I.IPROD = K.FPROD
            WHERE I.IVEND = ? AND K.FTYPE = 'F' AND {$item}
              AND K.FRDTE BETWEEN ? AND ?

            UNION ALL {$mmvo}
            UNION ALL {$mnaoTier1}
            UNION ALL {$mnaoTier2}
            UNION ALL {$toyota}";

        $range = [$this->vendor, $from8, $to8];

        return $this->select($sql, array_merge($range, $range, $range, $range, $range, $range));
    }

    /**
     * Next process per part (YPU202B @GLIN): routing (FRT) with the vendor as
     * work center, then the description of the work center in ROPDS2 (LWK).
     *
     * @param  array<int, string>  $parts
     * @return array<string, string>
     */
    private function nextProcesses(array $parts): array
    {
        $workCenters = [];

        foreach (array_chunk($parts, 200) as $chunk) {
            $marks = implode(',', array_fill(0, count($chunk), '?'));
            $rows = $this->select(
                "SELECT RPROD, ROPDS2 FROM {$this->lib}.FRT WHERE RWRKC = ? AND RPROD IN ({$marks})",
                array_merge([$this->vendor], $chunk)
            );
            foreach ($rows as $row) {
                if (! isset($workCenters[$row['RPROD']]) && ctype_digit(trim($row['ROPDS2']))) {
                    $workCenters[$row['RPROD']] = (int) trim($row['ROPDS2']);
                }
            }
        }

        $codes = array_values(array_unique($workCenters));
        $names = [];
        foreach (array_chunk($codes, 200) as $chunk) {
            $marks = implode(',', array_fill(0, count($chunk), '?'));
            foreach ($this->select("SELECT WWRKC, WDESC FROM {$this->lib}.LWK WHERE WWRKC IN ({$marks})", $chunk) as $row) {
                $names[(int) $row['WWRKC']] = $row['WDESC'];
            }
        }

        return array_map(fn (int $code) => $names[$code] ?? '', $workCenters);
    }

    /**
     * Runs a query on Infor. The ODBC driver returns upper-case column names and
     * fixed-width CHAR values, so values are right-trimmed.
     */
    private function select(string $sql, array $bindings = []): Collection
    {
        return collect(DB::connection(config('infor.connection'))->select($sql, $bindings))
            ->map(fn ($row) => array_map(
                fn ($value) => is_string($value) ? rtrim($value) : $value,
                array_change_key_case((array) $row, CASE_UPPER)
            ));
    }

    private function boxCount(mixed $quantity, mixed $snp): int
    {
        $snp = $this->number($snp);

        return $snp > 0 ? (int) ceil($this->number($quantity) / $snp) : 1;
    }

    private function number(mixed $value): int
    {
        return (int) round((float) $value);
    }

    private function pad(mixed $number, int $length): string
    {
        return substr(str_pad((string) $this->number($number), $length, '0', STR_PAD_LEFT), -$length);
    }

    /** Infor date (YYYYMMDD or CYYMMDD) to a Carbon date. */
    private function toDate(mixed $value): ?CarbonImmutable
    {
        $value = $this->number($value);
        if ($value <= 0) {
            return null;
        }
        if ($value < 19000000) {
            $value += 19000000;
        }

        return CarbonImmutable::createFromFormat('!Ymd', (string) $value) ?: null;
    }

    private function toDateString(mixed $value): string
    {
        return $this->toDate($value)?->toDateString() ?? '';
    }
}
