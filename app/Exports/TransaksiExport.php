<?php

namespace App\Exports;

use App\Models\Transaksi;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithDrawings;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use Maatwebsite\Excel\Events\BeforeSheet;
use Maatwebsite\Excel\Events\AfterSheet;

class TransaksiExport implements FromCollection, WithHeadings, WithStyles, WithEvents, WithDrawings
{
    protected $tahun;
    protected $bulan;
    protected $status;
    protected $search;
    protected $data;

    public function __construct($tahun, $bulan = null, $status = null, $search = null)
    {
        $this->tahun = $tahun;
        $this->bulan = $bulan;
        $this->status = $status;
        $this->search = $search;
    }

    public function collection(): Collection
    {
        $query = Transaksi::with(['user', 'alamatPengiriman'])
            ->whereYear('tanggal_transaksi', $this->tahun)
            ->whereHas('user', function ($q) {
                $q->where('role', 'customer');
            });

        if ($this->bulan) {
            $query->whereMonth('tanggal_transaksi', $this->bulan);
        }

        if ($this->status) {
            $query->where('status_pembayaran', $this->status);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('status_pembayaran', 'like', "%{$this->search}%")
                    ->orWhere('metode_pembayaran', 'like', "%{$this->search}%")
                    ->orWhereHas('user', function ($sub) {
                        $sub->where('name', 'like', "%{$this->search}%");
                    });
            });
        }

        $this->data = $query->orderBy('tanggal_transaksi', 'desc')->get();

        return new Collection([]);
    }

    public function headings(): array
    {
        return [];
    }

    public function drawings()
    {
        $logoPath = public_path('images/logo.png');

        if (!file_exists($logoPath)) {
            return [];
        }

        $drawing = new Drawing();
        $drawing->setName('Logo');
        $drawing->setDescription('NINA MOTOR Logo');
        $drawing->setPath($logoPath);
        $drawing->setHeight(100);
        $drawing->setCoordinates('A1');
        $drawing->setOffsetX(25);
        $drawing->setOffsetY(10);

        return [$drawing];
    }

    public function styles(Worksheet $sheet)
    {
        return [];
    }

    public function registerEvents(): array
    {
        return [
            BeforeSheet::class => function (BeforeSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $sheet->getColumnDimension('A')->setWidth(8);
                $sheet->getColumnDimension('B')->setWidth(8);
                $sheet->getColumnDimension('C')->setWidth(13);
                $sheet->getColumnDimension('D')->setWidth(25);
                $sheet->getColumnDimension('E')->setWidth(10);
                $sheet->getColumnDimension('F')->setWidth(15);
                $sheet->getColumnDimension('G')->setWidth(12);
                $sheet->getColumnDimension('H')->setWidth(15);

                $sheet->getRowDimension(1)->setRowHeight(28);
                $sheet->getRowDimension(2)->setRowHeight(22);
                $sheet->getRowDimension(3)->setRowHeight(20);
                $sheet->getRowDimension(4)->setRowHeight(20);
                $sheet->getRowDimension(5)->setRowHeight(5);
                $sheet->getRowDimension(6)->setRowHeight(25);
                $sheet->getRowDimension(7)->setRowHeight(20);

                $sheet->mergeCells('C1:H1');
                $sheet->setCellValue('C1', 'NINA MOTOR');
                $sheet->getStyle('C1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 20],
                    'alignment' => ['horizontal' => 'center', 'vertical' => 'center']
                ]);

                $sheet->mergeCells('C2:H2');
                $sheet->setCellValue('C2', 'Jl. Raya Denpasar - Gilimanuk No. 123, Denpasar, Bali 80361');
                $sheet->getStyle('C2')->applyFromArray([
                    'font' => ['size' => 10],
                    'alignment' => ['horizontal' => 'center', 'vertical' => 'center']
                ]);

                $sheet->mergeCells('C3:H3');
                $sheet->setCellValue('C3', 'Telp: (0361) 123456');
                $sheet->getStyle('C3')->applyFromArray([
                    'font' => ['size' => 10],
                    'alignment' => ['horizontal' => 'center', 'vertical' => 'center']
                ]);

                $sheet->mergeCells('C4:H4');
                $sheet->setCellValue('C4', 'Email: ninamotor@gmail.com');
                $sheet->getStyle('C4')->applyFromArray([
                    'font' => ['size' => 10],
                    'alignment' => ['horizontal' => 'center', 'vertical' => 'center']
                ]);

                $sheet->mergeCells('A5:H5');
                $sheet->getStyle('A5:H5')->getBorders()->getBottom()
                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK)
                    ->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF000000'));

                $title = "LAPORAN TRANSAKSI TAHUN {$this->tahun}";
                if ($this->bulan) {
                    $bulanName = Carbon::create()->month($this->bulan)->translatedFormat('F');
                    $title .= " - " . strtoupper($bulanName);
                }

                $sheet->mergeCells('A6:H6');
                $sheet->setCellValue('A6', $title);
                $sheet->getStyle('A6')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 13, 'underline' => true],
                    'alignment' => ['horizontal' => 'center', 'vertical' => 'center']
                ]);

                $headers = ['No', 'Tanggal', 'Nama Customer', 'Type', 'Metode', 'Status', 'Total (Rp)'];
                $cols = ['A', 'C', 'D', 'E', 'F', 'G', 'H'];

                $sheet->mergeCells('A7:B7');

                foreach ($headers as $index => $header) {
                    $sheet->setCellValue($cols[$index] . '7', $header);
                }

                $sheet->getStyle('A7:H7')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['argb' => 'FFFFFFFF'],
                        'size' => 11
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FF4CAF50'],
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['argb' => 'FF000000'],
                        ],
                    ],
                ]);
            },

            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $startRow = 8;

                foreach ($this->data as $index => $item) {
                    $row = $startRow + $index;

                    $sheet->mergeCells("A{$row}:B{$row}");
                    $sheet->setCellValue('A' . $row, $index + 1);
                    $sheet->setCellValue('C' . $row, Carbon::parse($item->tanggal_transaksi)->format('d/m/Y'));
                    $sheet->setCellValue('D' . $row, $item->user->nama ?? '-');
                    $sheet->setCellValue(
                        'E' . $row,
                        $item->type_pembelian == 0 ? 'Pembelian Sparepart' : 'Servis Motor'
                    );

                    $sheet->setCellValue('F' . $row, ucfirst(str_replace('_', ' ', $item->metode_pembayaran)));
                    $sheet->setCellValue('G' . $row, ucfirst($item->status_pembayaran));
                    $sheet->setCellValue('H' . $row, 'Rp ' . number_format($item->total ?? 0, 0, ',', '.'));
                }

                $lastRow = $startRow + $this->data->count() - 1;

                if ($this->data->count() > 0) {
                    $dataRange = 'A' . $startRow . ':H' . $lastRow;
                    $sheet->getStyle($dataRange)->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                'color' => ['argb' => 'FFCCCCCC'],
                            ],
                        ],
                        'alignment' => [
                            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                        ],
                    ]);

                    $sheet->getStyle('A' . $startRow . ':A' . $lastRow)->getAlignment()->setHorizontal('center');
                    $sheet->getStyle('C' . $startRow . ':C' . $lastRow)->getAlignment()->setHorizontal('center');
                    $sheet->getStyle('E' . $startRow . ':G' . $lastRow)->getAlignment()->setHorizontal('center');

                    $sheet->getStyle('H' . $startRow . ':H' . $lastRow)->getNumberFormat()
                        ->setFormatCode('#,##0');
                    $sheet->getStyle('H' . $startRow . ':H' . $lastRow)->getAlignment()->setHorizontal('right');

                    for ($i = $startRow; $i <= $lastRow; $i++) {
                        if (($i - $startRow) % 2 == 1) {
                            $sheet->getStyle("A{$i}:H{$i}")->getFill()
                                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                                ->getStartColor()->setARGB('FFF5F5F5');
                        }
                    }
                }

                $summaryRow = $lastRow + 2;

                $bankCount = $this->data->where('metode_pembayaran', 'bank_transfer')->count();
                $cashCount = $this->data->where('metode_pembayaran', 'cash')->count();
                $bankTotal = $this->data->where('metode_pembayaran', 'bank_transfer')->sum('total');
                $cashTotal = $this->data->where('metode_pembayaran', 'cash')->sum('total');
                $grandTotal = $this->data->sum('total');

                $sheet->mergeCells("F{$summaryRow}:H{$summaryRow}");
                $sheet->setCellValue("F{$summaryRow}", "RINGKASAN");
                $sheet->getStyle("F{$summaryRow}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12],
                    'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFC8E6C9'],
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ],
                    ],
                ]);

                $summaryRow++;

                $sheet->setCellValue("F{$summaryRow}", "Transfer Bank");
                $sheet->setCellValue("G{$summaryRow}", "{$bankCount} transaksi");
                $sheet->setCellValue("H{$summaryRow}", $bankTotal);
                $sheet->getStyle("H{$summaryRow}")->getNumberFormat()->setFormatCode('#,##0');

                $summaryRow++;

                $sheet->setCellValue("F{$summaryRow}", "Tunai");
                $sheet->setCellValue("G{$summaryRow}", "{$cashCount} transaksi");
                $sheet->setCellValue("H{$summaryRow}", $cashTotal);
                $sheet->getStyle("H{$summaryRow}")->getNumberFormat()->setFormatCode('#,##0');

                $summaryRow++;

                $sheet->setCellValue("F{$summaryRow}", "TOTAL KESELURUHAN");
                $sheet->setCellValue("G{$summaryRow}", $this->data->count() . " transaksi");
                $sheet->setCellValue("H{$summaryRow}", $grandTotal);
                $sheet->getStyle("H{$summaryRow}")->getNumberFormat()->setFormatCode('#,##0');

                $summaryStart = $lastRow + 3;
                $summaryEnd = $lastRow + 5;
                $sheet->getStyle("F{$summaryStart}:H{$summaryEnd}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ],
                    ],
                    'alignment' => [
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ],
                ]);

                $sheet->getStyle("F{$summaryStart}:F{$summaryEnd}")->getFont()->setBold(true);
                $sheet->getStyle("H{$summaryStart}:H{$summaryEnd}")->getAlignment()->setHorizontal('right');

                $sheet->getStyle("F{$summaryEnd}:H{$summaryEnd}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFFFEB3B'],
                    ],
                ]);
            },
        ];
    }
}
