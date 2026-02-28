<?php

namespace App\Domains\Reports\Services;

class SimplePdfReport
{
    /**
     * @param  array<int, string>  $lines
     */
    public static function build(array $lines): string
    {
        $contentLines = [];
        $contentLines[] = 'BT';
        $contentLines[] = '/F1 12 Tf';
        $contentLines[] = '72 720 Td';

        foreach ($lines as $index => $line) {
            $safeLine = preg_replace('/[^\x20-\x7E]/', '', $line ?? '') ?? '';
            if ($index > 0) {
                $contentLines[] = '0 -14 Td';
            }
            $contentLines[] = sprintf('(%s) Tj', self::escapeText($safeLine));
        }

        $contentLines[] = 'ET';

        $stream = implode("\n", $contentLines);

        $objects = [];
        $objects[] = "<< /Type /Catalog /Pages 2 0 R >>";
        $objects[] = "<< /Type /Pages /Kids [3 0 R] /Count 1 >>";
        $objects[] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>";
        $objects[] = "<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream";
        $objects[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1) . " 0 obj\n" . $object . "\nendobj\n";
        }

        $xrefStart = strlen($pdf);
        $pdf .= "xref\n";
        $pdf .= "0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";

        foreach (array_slice($offsets, 1) as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        $pdf .= "trailer\n";
        $pdf .= "<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n";
        $pdf .= $xrefStart . "\n";
        $pdf .= "%%EOF";

        return $pdf;
    }

    private static function escapeText(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }
}
