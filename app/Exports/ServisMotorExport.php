<?php

namespace App\Exports;

use App\Models\ServisMotor;
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

class ServisMotorExport implements FromCollection, WithHeadings, WithStyles, WithEvents, WithDrawings
{
    protected $tahun;
    protected $bulan;
    protected $status;
    protected $search;
    protected $data;

    public function __construct($tahun = null, $bulan = null, $status = null, $search = null)
    {
        $this->tahun = $tahun;
        $this->bulan = $bulan;
        $this->status = $status;
        $this->search = $search;
    }

    public function collection(): Collection
    {
        $query = ServisMotor::with(['user', 'transaksi'])->orderBy('created_at', 'desc');

        if ($this->tahun) {
            $query->whereYear('created_at', $this->tahun);
        }

        if ($this->bulan) {
            $query->whereMonth('created_at', $this->bulan);
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('no_kendaraan', 'like', "%{$this->search}%")
                    ->orWhere('jenis_motor', 'like', "%{$this->search}%")
                    ->orWhere('keluhan', 'like', "%{$this->search}%")
                    ->orWhereHas('user', function ($uq) {
                        $uq->where('nama', 'like', "%{$this->search}%");
                    });
            });
        }

        $this->data = $query->get();

        // DEBUG - hapus setelah testing
        \Log::info('Export Filter', [
            'tahun' => $this->tahun,
            'bulan' => $this->bulan,
            'status' => $this->status,
            'search' => $this->search,
            'total_data' => $this->data->count(),
            'sql' => $query->toSql()
        ]);

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
                $sheet->getColumnDimension('D')->setWidth(20);
                $sheet->getColumnDimension('E')->setWidth(15);
                $sheet->getColumnDimension('F')->setWidth(15);
                $sheet->getColumnDimension('G')->setWidth(25);
                $sheet->getColumnDimension('H')->setWidth(15);
                $sheet->getColumnDimension('I')->setWidth(15);

                $sheet->getRowDimension(1)->setRowHeight(28);
                $sheet->getRowDimension(2)->setRowHeight(22);
                $sheet->getRowDimension(3)->setRowHeight(20);
                $sheet->getRowDimension(4)->setRowHeight(20);
                $sheet->getRowDimension(5)->setRowHeight(5);
                $sheet->getRowDimension(6)->setRowHeight(25);
                $sheet->getRowDimension(7)->setRowHeight(20);

                $sheet->mergeCells('C1:I1');
                $sheet->setCellValue('C1', 'NINA MOTOR');
                $sheet->getStyle('C1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 20],
                    'alignment' => ['horizontal' => 'center', 'vertical' => 'center']
                ]);

                $sheet->mergeCells('C2:I2');
                $sheet->setCellValue('C2', 'Jl. Raya Sesetan No.312, Sesetan, Denpasar Selatan, Kota Denpasar, Bali 80223');
                $sheet->getStyle('C2')->applyFromArray([
                    'font' => ['size' => 10],
                    'alignment' => ['horizontal' => 'center', 'vertical' => 'center']
                ]);

                $sheet->mergeCells('C3:I3');
                $sheet->setCellValue('C3', 'Telp: 0852-3770-7724');
                $sheet->getStyle('C3')->applyFromArray([
                    'font' => ['size' => 10],
                    'alignment' => ['horizontal' => 'center', 'vertical' => 'center']
                ]);

                $sheet->mergeCells('C4:I4');
                $sheet->setCellValue('C4', 'Email: ninamotor@gmail.com');
                $sheet->getStyle('C4')->applyFromArray([
                    'font' => ['size' => 10],
                    'alignment' => ['horizontal' => 'center', 'vertical' => 'center']
                ]);

                $sheet->mergeCells('A5:I5');
                $sheet->getStyle('A5:I5')->getBorders()->getBottom()
                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK)
                    ->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF000000'));

                $title = "LAPORAN SERVIS MOTOR";
                if ($this->tahun) {
                    $title .= " TAHUN {$this->tahun}";
                }
                if ($this->bulan) {
                    $bulanName = Carbon::create()->month($this->bulan)->translatedFormat('F');
                    $title .= " - " . strtoupper($bulanName);
                }
                if ($this->status) {
                    $statusLabel = ucfirst(str_replace('_', ' ', $this->status));
                    $title .= " - STATUS: {$statusLabel}";
                }

                $sheet->mergeCells('A6:I6');
                $sheet->setCellValue('A6', $title);
                $sheet->getStyle('A6')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 13, 'underline' => true],
                    'alignment' => ['horizontal' => 'center', 'vertical' => 'center']
                ]);

                $headers = ['No', 'Tanggal', 'Nama Customer', 'No Kendaraan', 'Jenis Motor', 'Keluhan', 'Status', 'Harga (Rp)'];
                $cols = ['A', 'C', 'D', 'E', 'F', 'G', 'H', 'I'];

                $sheet->mergeCells('A7:B7');

                foreach ($headers as $index => $header) {
                    $sheet->setCellValue($cols[$index] . '7', $header);
                }

                $sheet->getStyle('A7:I7')->applyFromArray([
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
                    $sheet->setCellValue('C' . $row, Carbon::parse($item->created_at)->format('d/m/Y'));
                    $sheet->setCellValue('D' . $row, $item->user->nama ?? '-');
                    $sheet->setCellValue('E' . $row, $item->no_kendaraan);
                    $sheet->setCellValue('F' . $row, $item->jenis_motor);
                    $sheet->setCellValue('G' . $row, $item->keluhan);
                    $sheet->setCellValue('H' . $row, ucfirst(str_replace('_', ' ', $item->status)));
                    $sheet->setCellValue('I' . $row, $item->harga_servis ?? 0);
                }

                $lastRow = $startRow + $this->data->count() - 1;

                if ($this->data->count() > 0) {
                    $dataRange = 'A' . $startRow . ':I' . $lastRow;
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
                    $sheet->getStyle('H' . $startRow . ':H' . $lastRow)->getAlignment()->setHorizontal('center');

                    $sheet->getStyle('I' . $startRow . ':I' . $lastRow)->getNumberFormat()
                        ->setFormatCode('#,##0');
                    $sheet->getStyle('I' . $startRow . ':I' . $lastRow)->getAlignment()->setHorizontal('right');

                    for ($i = $startRow; $i <= $lastRow; $i++) {
                        if (($i - $startRow) % 2 == 1) {
                            $sheet->getStyle("A{$i}:I{$i}")->getFill()
                                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                                ->getStartColor()->setARGB('FFF5F5F5');
                        }
                    }
                }

                $summaryRow = $lastRow + 2;

                $pendingCount = $this->data->where('status', 'pending')->count();
                $rejectedCount = $this->data->where('status', 'rejected')->count();
                $inServiceCount = $this->data->where('status', 'in_service')->count();
                $doneCount = $this->data->where('status', 'done')->count();
                $pricedCount = $this->data->where('status', 'priced')->count();

                $pendingTotal = $this->data->where('status', 'pending')->sum('harga_servis');
                $rejectedTotal = $this->data->where('status', 'rejected')->sum('harga_servis');
                $inServiceTotal = $this->data->where('status', 'in_service')->sum('harga_servis');
                $doneTotal = $this->data->where('status', 'done')->sum('harga_servis');
                $pricedTotal = $this->data->where('status', 'priced')->sum('harga_servis');

                $totalHarga = $this->data->sum('harga_servis');

                $sheet->mergeCells("F{$summaryRow}:I{$summaryRow}");
                $sheet->setCellValue("F{$summaryRow}", "RINGKASAN BERDASARKAN STATUS");
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

                $sheet->setCellValue("F{$summaryRow}", "Menunggu");
                $sheet->setCellValue("G{$summaryRow}", "{$pendingCount} servis");
                $sheet->setCellValue("H{$summaryRow}", "Rp");
                $sheet->setCellValue("I{$summaryRow}", $pendingTotal);
                $sheet->getStyle("I{$summaryRow}")->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle("F{$summaryRow}:I{$summaryRow}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFFFF9C4');

                $summaryRow++;

                $sheet->setCellValue("F{$summaryRow}", "Ditolak");
                $sheet->setCellValue("G{$summaryRow}", "{$rejectedCount} servis");
                $sheet->setCellValue("H{$summaryRow}", "Rp");
                $sheet->setCellValue("I{$summaryRow}", $rejectedTotal);
                $sheet->getStyle("I{$summaryRow}")->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle("F{$summaryRow}:I{$summaryRow}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFFFCDD2');

                $summaryRow++;

                $sheet->setCellValue("F{$summaryRow}", "Dalam Proses");
                $sheet->setCellValue("G{$summaryRow}", "{$inServiceCount} servis");
                $sheet->setCellValue("H{$summaryRow}", "Rp");
                $sheet->setCellValue("I{$summaryRow}", $inServiceTotal);
                $sheet->getStyle("I{$summaryRow}")->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle("F{$summaryRow}:I{$summaryRow}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFB3E5FC');

                $summaryRow++;

                $sheet->setCellValue("F{$summaryRow}", "Selesai");
                $sheet->setCellValue("G{$summaryRow}", "{$doneCount} servis");
                $sheet->setCellValue("H{$summaryRow}", "Rp");
                $sheet->setCellValue("I{$summaryRow}", $doneTotal);
                $sheet->getStyle("I{$summaryRow}")->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle("F{$summaryRow}:I{$summaryRow}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFC8E6C9');

                $summaryRow++;

                $sheet->setCellValue("F{$summaryRow}", "Konfirmasi Pembayaran");
                $sheet->setCellValue("G{$summaryRow}", "{$pricedCount} servis");
                $sheet->setCellValue("H{$summaryRow}", "Rp");
                $sheet->setCellValue("I{$summaryRow}", $pricedTotal);
                $sheet->getStyle("I{$summaryRow}")->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle("F{$summaryRow}:I{$summaryRow}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFD1C4E9');

                $summaryRow++;

                $sheet->setCellValue("F{$summaryRow}", "TOTAL PENDAPATAN");
                $sheet->setCellValue("G{$summaryRow}", $this->data->count() . " servis");
                $sheet->setCellValue("H{$summaryRow}", "Rp");
                $sheet->setCellValue("I{$summaryRow}", $totalHarga);
                $sheet->getStyle("I{$summaryRow}")->getNumberFormat()->setFormatCode('#,##0');

                $summaryStart = $lastRow + 3;
                $summaryEnd = $lastRow + 8;
                $sheet->getStyle("F{$summaryStart}:I{$summaryEnd}")->applyFromArray([
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
                $sheet->getStyle("I{$summaryStart}:I{$summaryEnd}")->getAlignment()->setHorizontal('right');

                $sheet->getStyle("F{$summaryEnd}:I{$summaryEnd}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11, 'color' => ['argb' => 'FFFFFFFF']],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FF4CAF50'],
                    ],
                ]);
            },
        ];
    }
}
