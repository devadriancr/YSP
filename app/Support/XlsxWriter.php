<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use RuntimeException;
use ZipArchive;

/**
 * Minimal .xlsx writer (one sheet) on top of ZipArchive, so no spreadsheet package is
 * needed: text, numbers and dates, a bold header row that stays frozen with a filter,
 * and column widths. Excel, LibreOffice and Google Sheets open it.
 */
final class XlsxWriter
{
    public const DEFAULT = 0;

    public const HEADER = 1;

    public const DATE = 2;

    public const NUMBER = 3;

    public const TITLE = 4;

    /** @var list<array{cells: list<mixed>, style: ?int}> */
    private array $rows = [];

    /** @var list<float> */
    private array $widths = [];

    private ?int $headerRow = null;

    private int $headerColumns = 0;

    public function __construct(private string $sheetName = 'Hoja1') {}

    /** @param  list<float>  $widths  in characters, one per column */
    public function widths(array $widths): self
    {
        $this->widths = $widths;

        return $this;
    }

    /**
     * Each cell is a string, int, float, date or null; the style defaults by type
     * (numbers with thousands separator, dates as dd/mm/yyyy).
     */
    public function row(array $cells = [], ?int $style = null): self
    {
        $this->rows[] = ['cells' => array_values($cells), 'style' => $style];

        return $this;
    }

    // Column titles: bold, frozen while scrolling and with a filter on every column
    public function header(array $titles): self
    {
        $this->row($titles, self::HEADER);
        $this->headerRow = count($this->rows);
        $this->headerColumns = count($titles);

        return $this;
    }

    public function render(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not create the Excel file.');
        }

        $zip->addFromString('[Content_Types].xml', $this->contentTypes());
        $zip->addFromString('_rels/.rels', $this->rootRels());
        $zip->addFromString('xl/workbook.xml', $this->workbook());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRels());
        $zip->addFromString('xl/styles.xml', $this->styles());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->sheet());
        $zip->close();

        $content = file_get_contents($path);
        unlink($path);

        return $content;
    }

    private function sheet(): string
    {
        $views = $this->headerRow
            ? '<sheetViews><sheetView workbookViewId="0"><pane ySplit="'.$this->headerRow.'" topLeftCell="A'.($this->headerRow + 1).'" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            : '';

        $cols = '';
        foreach ($this->widths as $i => $width) {
            $cols .= '<col min="'.($i + 1).'" max="'.($i + 1).'" width="'.$width.'" customWidth="1"/>';
        }
        $cols = $cols ? "<cols>{$cols}</cols>" : '';

        $data = '';
        foreach ($this->rows as $r => $row) {
            $cells = '';
            foreach ($row['cells'] as $c => $value) {
                $cells .= $this->cell(self::column($c).($r + 1), $value, $row['style']);
            }
            $data .= '<row r="'.($r + 1).'">'.$cells.'</row>';
        }

        $filter = $this->filterRange() ? '<autoFilter ref="'.$this->filterRange().'"/>' : '';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .$views.$cols.'<sheetData>'.$data.'</sheetData>'.$filter.'</worksheet>';
    }

    private function cell(string $ref, mixed $value, ?int $style): string
    {
        if ($value === null || $value === '') {
            return $style ? '<c r="'.$ref.'" s="'.$style.'"/>' : '';
        }

        if ($value instanceof CarbonInterface) {
            // Excel counts days from 1899-12-30
            $serial = (int) CarbonImmutable::create(1899, 12, 30)->diffInDays($value->startOfDay());

            return '<c r="'.$ref.'" s="'.($style ?? self::DATE).'"><v>'.$serial.'</v></c>';
        }

        if (is_int($value) || is_float($value)) {
            return '<c r="'.$ref.'" s="'.($style ?? self::NUMBER).'"><v>'.$value.'</v></c>';
        }

        return '<c r="'.$ref.'" s="'.($style ?? self::DEFAULT).'" t="inlineStr"><is><t xml:space="preserve">'.self::xml((string) $value).'</t></is></c>';
    }

    private function filterRange(): ?string
    {
        if (! $this->headerRow || $this->headerColumns === 0) {
            return null;
        }

        return 'A'.$this->headerRow.':'.self::column($this->headerColumns - 1).max($this->headerRow, count($this->rows));
    }

    private function workbook(): string
    {
        $name = self::xml($this->safeSheetName());
        $filter = $this->filterRange()
            ? '<definedNames><definedName name="_xlnm._FilterDatabase" localSheetId="0" hidden="1">\''
                .str_replace("'", "''", $name).'\'!'.preg_replace('/([A-Z]+)(\d+)/', '\$$1\$$2', $this->filterRange()).'</definedName></definedNames>'
            : '';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="'.$name.'" sheetId="1" r:id="rId1"/></sheets>'.$filter.'</workbook>';
    }

    // Excel sheet names: 31 characters at most and none of : \ / ? * [ ]
    private function safeSheetName(): string
    {
        return mb_substr(str_replace([':', '\\', '/', '?', '*', '[', ']'], ' ', $this->sheetName), 0, 31) ?: 'Hoja1';
    }

    private function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<numFmts count="1"><numFmt numFmtId="164" formatCode="dd/mm/yyyy"/></numFmts>'
            .'<fonts count="3"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="14"/><name val="Calibri"/></font></fonts>'
            .'<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FFF2F4F7"/><bgColor indexed="64"/></patternFill></fill></fills>'
            .'<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border>'
            .'<border><left/><right/><top/><bottom style="thin"><color rgb="FFD0D5DD"/></bottom><diagonal/></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="5">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            .'<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>'
            .'<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            .'<xf numFmtId="3" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            .'<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            .'</cellXfs>'
            .'<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            .'</styleSheet>';
    }

    private function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'</Types>';
    }

    private function rootRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private function workbookRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>';
    }

    // 0 => A, 25 => Z, 26 => AA
    private static function column(int $index): string
    {
        $name = '';
        for ($index++; $index > 0; $index = intdiv($index - 1, 26)) {
            $name = chr(65 + ($index - 1) % 26).$name;
        }

        return $name;
    }

    private static function xml(string $text): string
    {
        // Control characters are not valid in XML 1.0
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $text) ?? '';

        return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
