<?php

namespace App\Domains\Maintenance\Services;

/**
 * Generates a minimal but valid XLSX (Office Open XML) file using a pure-PHP
 * ZIP writer — no ZipArchive or any other extension required.
 *
 * The ZIP uses only STORED (uncompressed) entries, which is perfectly legal for
 * XLSX and keeps the implementation dependency-free.
 */
class SimpleXlsxExport
{
    /** @var array<int, array<int, scalar|null>> */
    private array $rows = [];

    /** @var array<int, string> */
    private array $headers = [];

    /**
     * @param  array<int, string>  $headers
     */
    public function setHeaders(array $headers): static
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * @param  array<int, array<int, scalar|null>>  $rows
     */
    public function addRows(array $rows): static
    {
        $this->rows = $rows;

        return $this;
    }

    /**
     * Build and return the raw XLSX binary string.
     */
    public function build(): string
    {
        /** @var array<string, string> $files filename => content */
        $files = [];

        // --- shared strings -------------------------------------------------
        $sharedStrings = [];
        $sharedStringIndex = 0;
        $sharedStringMap = [];

        $resolveString = function (string $value) use (&$sharedStrings, &$sharedStringIndex, &$sharedStringMap): int {
            if (isset($sharedStringMap[$value])) {
                return $sharedStringMap[$value];
            }

            $sharedStrings[] = $value;
            $sharedStringMap[$value] = $sharedStringIndex;

            return $sharedStringIndex++;
        };

        // --- sheet rows -----------------------------------------------------
        $sheetRows = '';
        $rowIndex = 1;

        if ($this->headers !== []) {
            $sheetRows .= "<row r=\"{$rowIndex}\">";
            $colIndex = 0;
            foreach ($this->headers as $header) {
                $cellRef = $this->columnLetter($colIndex).$rowIndex;
                $ssi = $resolveString((string) $header);
                $sheetRows .= "<c r=\"{$cellRef}\" t=\"s\" s=\"1\"><v>{$ssi}</v></c>";
                $colIndex++;
            }
            $sheetRows .= '</row>';
            $rowIndex++;
        }

        foreach ($this->rows as $row) {
            $sheetRows .= "<row r=\"{$rowIndex}\">";
            $colIndex = 0;
            foreach ($row as $cell) {
                $cellRef = $this->columnLetter($colIndex).$rowIndex;
                if ($cell === null || $cell === '') {
                    $sheetRows .= "<c r=\"{$cellRef}\"/>";
                } elseif (is_numeric($cell)) {
                    $sheetRows .= "<c r=\"{$cellRef}\"><v>".$cell.'</v></c>';
                } else {
                    $ssi = $resolveString((string) $cell);
                    $sheetRows .= "<c r=\"{$cellRef}\" t=\"s\"><v>{$ssi}</v></c>";
                }
                $colIndex++;
            }
            $sheetRows .= '</row>';
            $rowIndex++;
        }

        // --- XML parts ------------------------------------------------------
        $files['xl/sharedStrings.xml'] =
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="'
            .count($sharedStrings).'" uniqueCount="'.count($sharedStrings).'">'
            .implode('', array_map(
                fn (string $s) => '<si><t>'.htmlspecialchars($s, ENT_XML1, 'UTF-8').'</t></si>',
                $sharedStrings
            ))
            .'</sst>';

        $files['xl/worksheets/sheet1.xml'] =
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetData>'.$sheetRows.'</sheetData>'
            .'</worksheet>';

        $files['xl/styles.xml'] =
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="2">'
            .'<font><sz val="11"/><name val="Calibri"/></font>'
            .'<font><b/><sz val="11"/><name val="Calibri"/></font>'
            .'</fonts>'
            .'<fills count="2">'
            .'<fill><patternFill patternType="none"/></fill>'
            .'<fill><patternFill patternType="gray125"/></fill>'
            .'</fills>'
            .'<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs>'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            .'<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            .'</cellXfs>'
            .'</styleSheet>';

        $files['xl/workbook.xml'] =
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            .'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="Maintenance Requests" sheetId="1" r:id="rId1"/></sheets>'
            .'</workbook>';

        $files['xl/_rels/workbook.xml.rels'] =
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>'
            .'<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>';

        $files['_rels/.rels'] =
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';

        $files['[Content_Types].xml'] =
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'</Types>';

        return $this->buildZip($files);
    }

    // -------------------------------------------------------------------------
    // Pure-PHP STORED-only ZIP writer
    // -------------------------------------------------------------------------

    /**
     * Build a ZIP archive (STORED, no compression) from an associative array
     * of filename => content pairs.
     *
     * @param  array<string, string>  $files
     */
    private function buildZip(array $files): string
    {
        $centralDirectory = '';
        $localHeaders = '';
        $offset = 0;

        foreach ($files as $name => $data) {
            $nameBytes = $name;
            $nameLen = strlen($nameBytes);
            $dataLen = strlen($data);
            $crc = crc32($data);
            $dosTime = $this->dosDateTime();

            // Local file header
            $localHeader =
                "\x50\x4b\x03\x04"       // signature
                ."\x14\x00"             // version needed (2.0)
                ."\x00\x00"             // general purpose bit flag
                ."\x00\x00"             // compression method: STORED
                .pack('V', $dosTime)    // last mod time & date
                .pack('V', $crc)        // CRC-32
                .pack('V', $dataLen)    // compressed size
                .pack('V', $dataLen)    // uncompressed size
                .pack('v', $nameLen)    // filename length
                ."\x00\x00"             // extra field length
                .$nameBytes;

            $localHeaders .= $localHeader.$data;

            // Central directory entry
            $centralDirectory .=
                "\x50\x4b\x01\x02"       // signature
                ."\x3f\x00"             // version made by (6.3 / Unix)
                ."\x14\x00"             // version needed
                ."\x00\x00"             // general purpose bit flag
                ."\x00\x00"             // compression method: STORED
                .pack('V', $dosTime)    // last mod time & date
                .pack('V', $crc)        // CRC-32
                .pack('V', $dataLen)    // compressed size
                .pack('V', $dataLen)    // uncompressed size
                .pack('v', $nameLen)    // filename length
                ."\x00\x00"             // extra field length
                ."\x00\x00"             // file comment length
                ."\x00\x00"             // disk number start
                ."\x00\x00"             // internal attributes
                ."\x00\x00\x00\x00"     // external attributes
                .pack('V', $offset)     // relative offset of local header
                .$nameBytes;

            $offset += strlen($localHeader) + $dataLen;
        }

        $cdLen = strlen($centralDirectory);
        $fileCount = count($files);

        // End of central directory record
        $endOfCd =
            "\x50\x4b\x05\x06"           // signature
            ."\x00\x00"                 // disk number
            ."\x00\x00"                 // disk with central dir start
            .pack('v', $fileCount)      // entries on this disk
            .pack('v', $fileCount)      // total entries
            .pack('V', $cdLen)          // central dir size
            .pack('V', $offset)         // central dir offset
            ."\x00\x00";                // comment length

        return $localHeaders.$centralDirectory.$endOfCd;
    }

    /**
     * Pack current datetime into a MS-DOS datetime double-word.
     */
    private function dosDateTime(): int
    {
        $t = getdate();
        $time = ($t['hours'] << 11) | ($t['minutes'] << 5) | ($t['seconds'] >> 1);
        $date = (($t['year'] - 1980) << 9) | ($t['mon'] << 5) | $t['mday'];

        return ($date << 16) | $time;
    }

    private function columnLetter(int $index): string
    {
        $letter = '';
        $index++;
        while ($index > 0) {
            $index--;
            $letter = chr(65 + ($index % 26)).$letter;
            $index = intdiv($index, 26);
        }

        return $letter;
    }
}
