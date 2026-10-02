<?php

namespace App\Support\Export;

use RuntimeException;
use ZipArchive;

/**
 * Penulis .docx minimal (ZipArchive + XML) untuk laporan: judul, paragraf, dan
 * tabel berlebar kolom tetap pada halaman A4 mendatar. Dipakai supaya export
 * Word tidak butuh pustaka tambahan.
 */
class SimpleDocx
{
    /** Lebar area isi A4 mendatar dengan margin 2 cm, dalam twip (1/20 pt). */
    public const CONTENT_WIDTH = 14570;

    private string $body = '';

    public function heading(string $text, int $size = 28, string $align = 'center'): static
    {
        return $this->paragraph($text, ['bold' => true, 'size' => $size, 'align' => $align, 'color' => '003D7C', 'after' => 60]);
    }

    /** @param  array{bold?: bool, size?: int, align?: string, color?: string, after?: int, italic?: bool}  $options */
    public function paragraph(string $text, array $options = []): static
    {
        $this->body .= self::paragraphXml($text, $options);

        return $this;
    }

    /**
     * @param  array<int, array<int, string|array{text: string, bold?: bool}>>  $rows
     * @param  array<int, int>  $widths  lebar tiap kolom dalam twip
     * @param  array{header?: bool, borders?: bool, size?: int}  $options
     */
    public function table(array $rows, array $widths, array $options = []): static
    {
        $borders = $options['borders'] ?? true;
        $size = $options['size'] ?? 18;
        $border = fn (string $side) => $borders ? "<w:{$side} w:val=\"single\" w:sz=\"4\" w:space=\"0\" w:color=\"8FA1B6\"/>" : "<w:{$side} w:val=\"nil\"/>";

        // Urutan anak tblPr mengikuti skema OOXML (tblW, tblBorders, tblLayout, tblCellMar): Word menolak urutan lain.
        $xml = '<w:tbl><w:tblPr><w:tblW w:w="'.array_sum($widths).'" w:type="dxa"/>'
            .'<w:tblBorders>'.$border('top').$border('left').$border('bottom').$border('right').$border('insideH').$border('insideV').'</w:tblBorders>'
            .'<w:tblLayout w:type="fixed"/>'
            .'<w:tblCellMar><w:top w:w="60" w:type="dxa"/><w:left w:w="90" w:type="dxa"/><w:bottom w:w="60" w:type="dxa"/><w:right w:w="90" w:type="dxa"/></w:tblCellMar>'
            .'</w:tblPr><w:tblGrid>';
        foreach ($widths as $width) {
            $xml .= "<w:gridCol w:w=\"{$width}\"/>";
        }
        $xml .= '</w:tblGrid>';

        foreach (array_values($rows) as $rowIndex => $row) {
            $isHeader = ($options['header'] ?? false) && $rowIndex === 0;
            // cantSplit: satu kegiatan tidak terpotong di tengah halaman; tblHeader: judul kolom diulang tiap halaman.
            $xml .= '<w:tr><w:trPr><w:cantSplit/>'.($isHeader ? '<w:tblHeader/>' : '').'</w:trPr>';

            foreach (array_values($row) as $columnIndex => $cell) {
                [$text, $bold] = is_array($cell) ? [$cell['text'], $cell['bold'] ?? false] : [$cell, false];
                $xml .= '<w:tc><w:tcPr><w:tcW w:w="'.($widths[$columnIndex] ?? 1000).'" w:type="dxa"/>'
                    .($isHeader ? '<w:shd w:val="clear" w:color="auto" w:fill="E8F0F9"/>' : '')
                    .'</w:tcPr>';

                // Tiap baris teks jadi paragraf sendiri supaya ganti baris di catatan tetap terbaca.
                foreach (preg_split('/\R/u', (string) $text) ?: [''] as $line) {
                    $xml .= self::paragraphXml($line, ['bold' => $bold || $isHeader, 'size' => $size, 'after' => 0]);
                }
                $xml .= '</w:tc>';
            }
            $xml .= '</w:tr>';
        }

        $this->body .= $xml.'</w:tbl>';

        return $this;
    }

    /** Isi berkas .docx sebagai string biner. */
    public function toString(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'docx');
        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Tidak bisa membuat berkas Word sementara.');
        }

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            .'<Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>'
            .'</Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
            .'</Relationships>');
        $zip->addFromString('word/_rels/document.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>');
        $zip->addFromString('word/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            .'<w:docDefaults><w:rPrDefault><w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri" w:cs="Calibri"/><w:sz w:val="20"/><w:szCs w:val="20"/><w:lang w:val="id-ID"/></w:rPr></w:rPrDefault>'
            .'<w:pPrDefault><w:pPr><w:spacing w:after="80" w:line="252" w:lineRule="auto"/></w:pPr></w:pPrDefault></w:docDefaults>'
            .'<w:style w:type="paragraph" w:default="1" w:styleId="Normal"><w:name w:val="Normal"/></w:style>'
            .'</w:styles>');
        $zip->addFromString('word/document.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'
            .$this->body
            // A4 mendatar, margin 2 cm.
            .'<w:sectPr><w:pgSz w:w="16838" w:h="11906" w:orient="landscape"/><w:pgMar w:top="1134" w:right="1134" w:bottom="1134" w:left="1134" w:header="567" w:footer="567" w:gutter="0"/></w:sectPr>'
            .'</w:body></w:document>');
        $zip->close();

        $contents = (string) file_get_contents($path);
        unlink($path);

        return $contents;
    }

    /** @param  array{bold?: bool, size?: int, align?: string, color?: string, after?: int, italic?: bool}  $options */
    private static function paragraphXml(string $text, array $options): string
    {
        $runProperties = ($options['bold'] ?? false ? '<w:b/>' : '')
            .($options['italic'] ?? false ? '<w:i/>' : '')
            .(isset($options['color']) ? '<w:color w:val="'.$options['color'].'"/>' : '')
            .(isset($options['size']) ? '<w:sz w:val="'.$options['size'].'"/><w:szCs w:val="'.$options['size'].'"/>' : '');

        // Skema OOXML: spacing harus mendahului jc di dalam pPr.
        $paragraphProperties = (isset($options['after']) ? '<w:spacing w:after="'.$options['after'].'"/>' : '')
            .(isset($options['align']) ? '<w:jc w:val="'.$options['align'].'"/>' : '');

        return '<w:p>'.($paragraphProperties !== '' ? "<w:pPr>{$paragraphProperties}</w:pPr>" : '')
            .'<w:r>'.($runProperties !== '' ? "<w:rPr>{$runProperties}</w:rPr>" : '')
            .'<w:t xml:space="preserve">'.SimpleXlsx::escape($text).'</w:t></w:r></w:p>';
    }
}
