<?php

namespace App\Excel;

use App\Support\XlsxWriter;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Unreceived reports (YPU705B parts / YPU715B raw material) as an Excel sheet:
 * the same columns as the PDF, so they can be filtered and sorted.
 */
final class UnreceivedReport
{
    /**
     * @param  'parts'|'materials'  $type
     * @param  list<array<string, mixed>>  $rows  from App\Services\SupplierDocuments::unreceived()
     */
    public function render(string $type, array $rows, int $vendor, string $vendorName, CarbonInterface $issued, string $company): string
    {
        $materials = $type === 'materials';
        $title = $materials ? __('Materia prima no recibida') : __('Piezas no recibidas');

        $sheet = (new XlsxWriter($title))
            ->widths($materials ? [16, 24, 16, 12, 20, 11, 11, 11, 12, 16, 16, 11, 8] : [16, 30, 12, 16, 16, 11])
            ->row([$title], XlsxWriter::TITLE)
            ->row([$company])
            ->row([__('Proveedor'), "{$vendor} · {$vendorName}"])
            ->row([__('Fecha de emisión'), $issued])
            ->row()
            ->header($materials
                ? [__('Orden de compra'), __('Número de parte'), __('Código de material'), __('Material madre'), __('Símbolo'),
                    __('Espesor'), __('Ancho'), __('Largo'), __('Fecha de entrega'), __('Ordenado'), __('Pendiente'), __('Días de atraso'), __('Unidad')]
                : [__('Orden de compra'), __('Número de parte'), __('Fecha de entrega'), __('Ordenado'), __('Pendiente'), __('Días de atraso')]);

        usort($rows, fn (array $a, array $b) => $a['order_no'] <=> $b['order_no']);

        foreach ($rows as $row) {
            $due = CarbonImmutable::parse($row['due_date']);
            $quantities = [(float) $row['quantity'], (float) $row['pending'], $row['days_late']];

            $sheet->row($materials
                ? [$row['order_no'], $row['part'], $row['description'], $row['material'], $row['symbol'],
                    (float) $row['thickness'], (float) $row['width'], (float) $row['length'], $due, ...$quantities, $row['unit']]
                : [$row['order_no'], $row['part'], $due, ...$quantities]);
        }

        return $sheet->render();
    }
}
