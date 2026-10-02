<?php

namespace App\Support\Export;

use DateTimeInterface;
use RuntimeException;
use ZipArchive;

/**
 * Penulis .xlsx minimal (ZipArchive + XML) untuk tabel laporan: beberapa sheet,
 * lebar kolom, baris judul beku, autofilter, dan beberapa gaya sel. Dipakai
 * supaya export Excel tidak butuh pustaka tambahan.
 *
 * Semua teks ditulis sebagai inline string, jadi isi yang diawali "=" tetap
 * teks dan tidak pernah dieksekusi sebagai formula.
 */
class SimpleXlsx
{
    /** Kunci gaya -> indeks cellXfs di styles.xml. */
    private const STYLES = ['default' => 0, 'header' => 1, 'text' => 2, 'number' => 3, 'date' => 4, 'label' => 5, 'title' => 6];

    /** @var array<int, array{name: string, xml: string}> */
    private array $sheets = [];

    /**
     * @param  array<int, array<int, mixed>>  $rows  sel berupa nilai, atau ['value' => ..., 'style' => kunci STYLES]
     * @param  array{widths?: array<int, int|float>, freezeHeader?: bool, autoFilter?: bool}  $options
     */
    public function addSheet(string $name, array $rows, array $options = []): static
    {
        $columnCount = max(array_map('count', $rows) ?: [1]);

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';

        if ($options['freezeHeader'] ?? false) {
            $xml .= '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>';
        }

        if ($widths = $options['widths'] ?? []) {
            $xml .= '<cols>';
            foreach (array_values($widths) as $index => $width) {
                $xml .= sprintf('<col min="%1$d" max="%1$d" width="%2$s" customWidth="1"/>', $index + 1, $width);
            }
            $xml .= '</cols>';
        }

        $xml .= '<sheetData>';
        foreach (array_values($rows) as $rowIndex => $row) {
            $xml .= '<row r="'.($rowIndex + 1).'">';
            foreach (array_values($row) as $columnIndex => $cell) {
                $xml .= $this->cell(self::columnLetter($columnIndex).($rowIndex + 1), $cell);
            }
            $xml .= '</row>';
        }
        $xml .= '</sheetData>';

        if (($options['autoFilter'] ?? false) && count($rows) > 0) {
            $xml .= '<autoFilter ref="A1:'.self::columnLetter($columnCount - 1).count($rows).'"/>';
        }

        $this->sheets[] = ['name' => $this->sheetName($name), 'xml' => $xml.'</worksheet>'];

        return $this;
    }

    /** Isi berkas .xlsx sebagai string biner. */
    public function toString(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Tidak bisa membuat berkas Excel sementara.');
        }

        $sheetOverrides = $sheetEntries = $sheetRelations = '';
        foreach ($this->sheets as $index => $sheet) {
            $number = $index + 1;
            $zip->addFromString("xl/worksheets/sheet{$number}.xml", $sheet['xml']);
            $sheetOverrides .= "<Override PartName=\"/xl/worksheets/sheet{$number}.xml\" ContentType=\"application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml\"/>";
            $sheetEntries .= '<sheet name="'.self::escape($sheet['name'])."\" sheetId=\"{$number}\" r:id=\"rId{$number}\"/>";
            $sheetRelations .= "<Relationship Id=\"rId{$number}\" Type=\"http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet\" Target=\"worksheets/sheet{$number}.xml\"/>";
        }
        $stylesRelation = count($this->sheets) + 1;

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .$sheetOverrides.'</Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets>'.$sheetEntries.'</sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'.$sheetRelations
            ."<Relationship Id=\"rId{$stylesRelation}\" Type=\"http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles\" Target=\"styles.xml\"/>"
            .'</Relationships>');
        $zip->addFromString('xl/styles.xml', $this->stylesXml());
        $zip->close();

        $contents = (string) file_get_contents($path);
        unlink($path);

        return $contents;
    }

    private function cell(string $reference, mixed $cell): string
    {
        [$value, $style] = is_array($cell) ? [$cell['value'] ?? null, $cell['style'] ?? 'default'] : [$cell, 'default'];
        $styleIndex = self::STYLES[$style] ?? 0;

        if ($value === null || $value === '') {
            return $styleIndex ? "<c r=\"{$reference}\" s=\"{$styleIndex}\"/>" : '';
        }

        if ($value instanceof DateTimeInterface) {
            // Nomor seri Excel: jumlah hari sejak 30 Des 1899, dalam zona waktu nilainya sendiri.
            $serial = ($value->getTimestamp() + $value->getOffset()) / 86400 + 25569;

            return "<c r=\"{$reference}\" s=\"{$styleIndex}\"><v>".floor($serial).'</v></c>';
        }

        if (is_int($value) || is_float($value)) {
            return "<c r=\"{$reference}\" s=\"{$styleIndex}\"><v>{$value}</v></c>";
        }

        return "<c r=\"{$reference}\" s=\"{$styleIndex}\" t=\"inlineStr\"><is><t xml:space=\"preserve\">".self::escape((string) $value).'</t></is></c>';
    }

    private function stylesXml(): string
    {
        $border = '<border><left style="thin"><color rgb="FFD8E1EC"/></left><right style="thin"><color rgb="FFD8E1EC"/></right><top style="thin"><color rgb="FFD8E1EC"/></top><bottom style="thin"><color rgb="FFD8E1EC"/></bottom><diagonal/></border>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<numFmts count="1"><numFmt numFmtId="164" formatCode="dd/mm/yyyy"/></numFmts>'
            .'<fonts count="4">'
            .'<font><sz val="11"/><name val="Calibri"/></font>'
            .'<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            .'<font><b/><sz val="11"/><name val="Calibri"/></font>'
            .'<font><b/><sz val="14"/><color rgb="FF003D7C"/><name val="Calibri"/></font>'
            .'</fonts>'
            .'<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FF003D7C"/><bgColor indexed="64"/></patternFill></fill></fills>'
            .'<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border>'.$border.'</borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="7">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            .'<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment vertical="top" horizontal="right"/></xf>'
            .'<xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1" applyAlignment="1"><alignment vertical="top" horizontal="left"/></xf>'
            .'<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment vertical="top"/></xf>'
            .'<xf numFmtId="0" fontId="3" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            .'</cellXfs>'
            .'<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            .'</styleSheet>';
    }

    /** Excel membatasi nama sheet 31 karakter tanpa : \ / ? * [ ]. */
    private function sheetName(string $name): string
    {
        return mb_substr((string) preg_replace('/[:\\\\\/?*\[\]]/', ' ', $name), 0, 31);
    }

    private static function columnLetter(int $index): string
    {
        $letters = '';
        for ($number = $index + 1; $number > 0; $number = intdiv($number - 1, 26)) {
            $letters = chr(65 + ($number - 1) % 26).$letters;
        }

        return $letters;
    }

    /** Escape XML dan buang karakter kontrol yang tidak sah di XML 1.0. */
    public static function escape(string $text): string
    {
        $text = (string) preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $text);

        return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
