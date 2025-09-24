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

class TransaksiExport implements FromCollection, WithHeadings, WithStyles, WithEvents
{
    protected $tahun;
    protected $search;

    public function __construct($tahun, $search = null)
    {
        $this->tahun = $tahun;
        $this->search = $search;
    }

    public function collection(): Collection
    {
        $query = Transaksi::with('user')
            ->whereYear('tanggal_transaksi', $this->tahun)
            ->whereHas('user', function ($q) {
                $q->where('role', 'customer');
            });

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('status_pembayaran', 'like', "%{$this->search}%")
                    ->orWhere('metode_pembayaran', 'like', "%{$this->search}%")
                    ->orWhereHas('user', function ($sub) {
                        $sub->where('name', 'like', "%{$this->search}%");
                    });
            });
        }

        $data = $query->get();

        return $data->map(function ($item, $i) {
            return [
                'No' => $i + 1,
                'Nama User' => $item->user->nama ?? '-',
                'Status Pembayaran' => ucfirst($item->status_pembayaran),
                'Metode Pembayaran' => ucfirst($item->metode_pembayaran),
                'Tanggal Transaksi' => Carbon::parse($item->tanggal_transaksi)->format('d M Y'),
            ];
        });
    }

    public function headings(): array
    {
        return ['No', 'Nama User', 'Status Pembayaran', 'Metode Pembayaran', 'Tanggal Transaksi'];
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
                $event->sheet->setCellValue('A1', $title);
                $event->sheet->mergeCells('A1:E1');
                $event->sheet->getDelegate()->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $event->sheet->getDelegate()->getStyle('A1')->getAlignment()->setHorizontal('center');
            },
        ];
    }
}
