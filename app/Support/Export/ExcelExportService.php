<?php

namespace App\Support\Export;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Generic genuine-.xlsx export helper built on PhpOffice/PhpSpreadsheet.
 * Deliberately generic (header row + row iterable in, streamed .xlsx out)
 * so any module — Students, Staff, Programmes, Admissions, Finance,
 * Attendance, Examinations — can reuse it without writing its own
 * PhpSpreadsheet boilerplate. Never call this with sensitive fields such
 * as password hashes; callers are responsible for choosing exportable
 * columns only.
 */
class ExcelExportService
{
    /**
     * @param  string  $filename  without extension
     * @param  string[]  $headers  column header labels, in column order
     * @param  iterable<array>  $rows  each row an indexed/associative array;
     *                                  values are written in array order
     */
    public static function download(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->fromArray($headers, null, 'A1');
        $highestColumn = $sheet->getHighestColumn();
        $sheet->getStyle("A1:{$highestColumn}1")->getFont()->setBold(true);

        $rowIndex = 2;
        foreach ($rows as $row) {
            $sheet->fromArray(array_values($row), null, "A{$rowIndex}");
            $rowIndex++;
        }

        foreach (range('A', $highestColumn) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
