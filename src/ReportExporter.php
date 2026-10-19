<?php

declare(strict_types=1);

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

final class ReportExporter
{
    public const HEADERS = [
        'Client',
        'Organization',
        'Client Email',
        'Client Phone',
        'Service Type',
        'Provider',
        'Expiry Date',
        'Cost',
        'Currency',
        'Billing Cycle',
        'Payment Account',
    ];

    /** @var list<string> */
    private const KEYS = [
        'client_name',
        'organization',
        'client_email',
        'client_phone',
        'service_type',
        'provider',
        'expiry_date',
        'cost',
        'currency',
        'billing_cycle',
        'payment_account',
    ];

    /**
     * @param list<array<string, mixed>> $rows
     * @param resource $stream
     */
    public function writeCsv(array $rows, $stream): void
    {
        if (!is_resource($stream)) {
            throw new InvalidArgumentException('CSV output must be a writable stream.');
        }

        fwrite($stream, "\xEF\xBB\xBF");
        fputcsv($stream, self::HEADERS, ',', '"', '');

        foreach ($rows as $row) {
            $values = [];
            foreach (self::KEYS as $key) {
                $values[] = $this->csvValue($row[$key] ?? null);
            }
            fputcsv($stream, $values, ',', '"', '');
        }
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @param array{client:string,service_type:string,date_range:string,generated_at:string} $context
     */
    public function writeXlsx(array $rows, string $destination, array $context): void
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('ServiceNotify')
            ->setTitle('Service expiry report')
            ->setSubject($context['date_range'])
            ->setDescription('Ownership-scoped ServiceNotify export.');

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Services');
        foreach (self::HEADERS as $index => $header) {
            $coordinate = Coordinate::stringFromColumnIndex($index + 1) . '1';
            $sheet->setCellValueExplicit($coordinate, $header, DataType::TYPE_STRING);
        }

        foreach ($rows as $rowIndex => $row) {
            $excelRow = $rowIndex + 2;
            foreach (self::KEYS as $columnIndex => $key) {
                $coordinate = Coordinate::stringFromColumnIndex($columnIndex + 1) . $excelRow;
                $value = $row[$key] ?? null;
                if ($key === 'cost' && $value !== null && $value !== '') {
                    $sheet->setCellValueExplicit($coordinate, (float) $value, DataType::TYPE_NUMERIC);
                    continue;
                }
                $sheet->setCellValueExplicit($coordinate, (string) ($value ?? ''), DataType::TYPE_STRING);
            }
        }

        $lastColumn = Coordinate::stringFromColumnIndex(count(self::HEADERS));
        $lastRow = max(1, count($rows) + 1);
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:' . $lastColumn . $lastRow);
        $sheet->getStyle('A1:' . $lastColumn . '1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '07110A']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '72E58C']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '3B8F50']]],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(24);
        foreach (range(1, count(self::HEADERS)) as $columnIndex) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($columnIndex))->setAutoSize(true);
        }
        if ($lastRow > 1) {
            $sheet->getStyle('H2:H' . $lastRow)->getNumberFormat()->setFormatCode('#,##0.00');
        }

        $details = $spreadsheet->createSheet();
        $details->setTitle('Report details');
        $details->fromArray([
            ['ServiceNotify export', ''],
            ['Client', $context['client']],
            ['Service type', $context['service_type']],
            ['Date range', $context['date_range']],
            ['Generated', $context['generated_at']],
            ['Rows', count($rows)],
        ]);
        $details->getStyle('A1:B1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '07110A']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '72E58C']],
        ]);
        $details->getStyle('A2:A6')->getFont()->setBold(true);
        $details->getColumnDimension('A')->setWidth(18);
        $details->getColumnDimension('B')->setWidth(40);
        $spreadsheet->setActiveSheetIndex(0);

        (new Xlsx($spreadsheet))->save($destination);
        $spreadsheet->disconnectWorksheets();
    }

    private function csvValue(mixed $value): string
    {
        $value = (string) ($value ?? '');

        return preg_match('/^[=+\-@]/u', $value) === 1 ? "'" . $value : $value;
    }
}
