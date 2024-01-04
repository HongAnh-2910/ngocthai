<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Sheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;

class ContactExport implements FromArray, ShouldAutoSize, WithEvents, WithTitle, WithStrictNullComparison
{
    private $data;
    private $row_count;
    private $merge;

    public function  __construct(array $data, $row_count, $merge = [])
    {
        $this->data = $data;
        $this->row_count = $row_count;
        $this->merge = $merge;
    }

    public function array(): array
    {
        return $this->data;
    }

    public function title(): string
    {
        return __('contact.customer');
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class    => function(AfterSheet $event) {
                Sheet::macro('styleCells', function (Sheet $sheet, string $cellRange, array $style) {
                    $sheet->getDelegate()->getStyle($cellRange)->applyFromArray($style);
                });

                $styleArray = [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['argb' => '00000000'],
                        ],
                    ],
                ];
                $event->sheet->getStyle('A1:I' . ($this->row_count - 1))->applyFromArray($styleArray);
                $event->sheet->getStyle('F5:I' . ($this->row_count - 1))->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                $event->sheet->getStyle('H4:I4')->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

                $event->sheet->styleCells('A1:H4', [
                    'font' => [
                        'bold' => true,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                ]);

                $event->sheet->styleCells('I4', [
                    'font' => [
                        'bold' => true,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                ]);

                $event->sheet->styleCells('I3', [
                    'font' => [
                        'bold' => true,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                ]);

                $event->sheet->getDelegate()->mergeCells('A1:I1');
                $event->sheet->getDelegate()->mergeCells('A2:A3');
                $event->sheet->getDelegate()->mergeCells('B2:B3');
                $event->sheet->getDelegate()->mergeCells('C2:C3');
                $event->sheet->getDelegate()->mergeCells('D2:D3');
                $event->sheet->getDelegate()->mergeCells('E2:E3');
                $event->sheet->getDelegate()->mergeCells('F2:G2');
                $event->sheet->getDelegate()->mergeCells('H2:I2');
                $event->sheet->getDelegate()->mergeCells('A4:G4');
            },
        ];
    }
}
