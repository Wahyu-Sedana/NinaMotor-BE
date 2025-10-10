<?php

namespace App\Exports;

use App\Models\Transaksi;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Events\BeforeSheet;
use Maatwebsite\Excel\Events\AfterSheet;

class TransaksiExport implements FromCollection, WithHeadings, WithStyles, WithEvents
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

        // Filter by month
        if ($this->bulan) {
            $query->whereMonth('tanggal_transaksi', $this->bulan);
        }

        // Filter by status
        if ($this->status) {
            $query->where('status_pembayaran', $this->status);
        }

        // Filter by search
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('status_pembayaran', 'like', "%{$this->search}%")
                    ->orWhere('metode_pembayaran', 'like', "%{$this->search}%")
                    ->orWhereHas('user', function ($sub) {
                        $sub->where('name', 'like', "%{$this->search}%");
                    });
            });
        }

        $this->data = $query->get();

        return $this->data->map(function ($item, $i) {
            $alamat = $item->alamatPengiriman;

            return [
                'No' => $i + 1,
                'Nama User' => $item->user->nama ?? '-',
                'Status Pembayaran' => ucfirst($item->status_pembayaran),
                'Metode Pembayaran' => ucfirst($item->metode_pembayaran),
                'Total' => $item->total ?? 0,
                'Tanggal Transaksi' => Carbon::parse($item->tanggal_transaksi)->format('d M Y'),
                'Type Pembelian' => ucfirst($item->type_pembelian ?? '-'),
                'Nama Penerima' => $alamat->nama_penerima ?? '-',
                'No Telp Penerima' => $alamat->no_telp_penerima ?? '-',
                'Alamat Lengkap' => $alamat->alamat_lengkap ?? '-',
                'Kurir' => strtoupper($item->kurir ?? '-'),
                'Service' => $item->service ?? '-',
                'Ongkir' => $item->ongkir ?? 0,
                'Estimasi' => $item->estimasi ?? '-',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'No',
            'Nama User',
            'Status Pembayaran',
            'Metode Pembayaran',
            'Total',
            'Tanggal Transaksi',
            'Type Pembelian',
            'Nama Penerima',
            'No Telp Penerima',
            'Alamat Lengkap',
            'Kurir',
            'Service',
            'Ongkir',
            'Estimasi'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            3 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFEFEFEF'],
                ],
                'borders' => [
                    'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN],
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            BeforeSheet::class => function (BeforeSheet $event) {
                $title = "Laporan Transaksi Tahun {$this->tahun}";

                if ($this->bulan) {
                    $bulanName = Carbon::create()->month($this->bulan)->translatedFormat('F');
                    $title .= " - Bulan {$bulanName}";
                }

                if ($this->status) {
                    $title .= " - Status: " . ucfirst($this->status);
                }

                $event->sheet->setCellValue('A1', $title);
                $event->sheet->mergeCells('A1:R1');
                $event->sheet->getDelegate()->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $event->sheet->getDelegate()->getStyle('A1')->getAlignment()->setHorizontal('center');
            },

            AfterSheet::class => function (AfterSheet $event) {
                $bankCount = $this->data->where('metode_pembayaran', 'bank_transfer')->count();
                $cashCount = $this->data->where('metode_pembayaran', 'cash')->count();

                $bankTotal = $this->data->where('metode_pembayaran', 'bank_transfer')->sum('total');
                $cashTotal = $this->data->where('metode_pembayaran', 'cash')->sum('total');
                $grandTotal = $this->data->sum('total');
                $totalOngkir = $this->data->sum('ongkir');

                $totalCount = $this->data->count();

                $lastRow = $this->data->count() + 3;

                $sheet = $event->sheet->getDelegate();

                $sheet->setCellValue("P" . ($lastRow + 1), "Total Bank Transfer");
                $sheet->setCellValue("Q" . ($lastRow + 1), $bankCount . " transaksi");
                $sheet->setCellValue("R" . ($lastRow + 1), "Rp " . number_format($bankTotal, 0, ',', '.'));

                $sheet->setCellValue("P" . ($lastRow + 2), "Total Cash");
                $sheet->setCellValue("Q" . ($lastRow + 2), $cashCount . " transaksi");
                $sheet->setCellValue("R" . ($lastRow + 2), "Rp " . number_format($cashTotal, 0, ',', '.'));

                $sheet->setCellValue("P" . ($lastRow + 3), "Total Ongkir");
                $sheet->setCellValue("Q" . ($lastRow + 3), "-");
                $sheet->setCellValue("R" . ($lastRow + 3), "Rp " . number_format($totalOngkir, 0, ',', '.'));

                $sheet->setCellValue("P" . ($lastRow + 4), "Total Semua");
                $sheet->setCellValue("Q" . ($lastRow + 4), $totalCount . " transaksi");
                $sheet->setCellValue("R" . ($lastRow + 4), "Rp " . number_format($grandTotal, 0, ',', '.'));

                $sheet->getStyle("P" . ($lastRow + 1) . ":P" . ($lastRow + 4))->getFont()->setBold(true);
                $sheet->getStyle("Q" . ($lastRow + 1) . ":R" . ($lastRow + 4))->getFont()->setBold(true);

                $sheet->getStyle("P" . ($lastRow + 4) . ":R" . ($lastRow + 4))->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFFFEB3B');

                // Auto-size columns
                foreach (range('A', 'R') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
            },
        ];
    }
}
