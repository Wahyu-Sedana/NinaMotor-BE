<?php

namespace App\Exports;

use App\Models\ServisMotor;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Events\BeforeSheet;

class ServisMotorExport implements FromCollection, WithHeadings, WithStyles, WithEvents
{
    protected $tahun;
    protected $bulan;
    protected $tanggal;
    protected $search;

    public function __construct($tahun = null, $bulan = null, $tanggal = null, $search = null)
    {
        $this->tahun = $tahun;
        $this->bulan = $bulan;
        $this->tanggal = $tanggal;
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

        $data = $query->get();

        return $data->map(function ($item, $i) {
            return [
                'No' => $i + 1,
                'Nama Customer' => $item->user->nama ?? '-',
                'No Kendaraan' => $item->no_kendaraan,
                'Jenis Motor' => $item->jenis_motor,
                'Keluhan' => $item->keluhan,
                'Status Servis' => ucfirst(str_replace('_', ' ', $item->status)),
                'Harga Servis' => $item->harga_servis ? number_format($item->harga_servis, 0, ',', '.') : '-',
                'Transaksi ID' => $item->transaksi_id ?? '-',
                'Metode Pembayaran' => $item->transaksi->metode_pembayaran ?? '-',
                'Tanggal Transaksi' => $item->transaksi && $item->transaksi->tanggal_transaksi
                    ? Carbon::parse($item->transaksi->tanggal_transaksi)->format('d M Y')
                    : '-',
                'Tanggal Servis' => Carbon::parse($item->created_at)->format('d M Y'),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'No',
            'Nama Customer',
            'No Kendaraan',
            'Jenis Motor',
            'Keluhan',
            'Status Servis',
            'Harga Servis',
            'Transaksi ID',
            'Metode Pembayaran',
            'Tanggal Transaksi',
            'Tanggal Servis'
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
                $title = "Laporan Servis Motor";
                if ($this->tahun) {
                    $title .= " Tahun {$this->tahun}";
                }
                $event->sheet->setCellValue('A1', $title);
                $event->sheet->mergeCells('A1:K1');
                $event->sheet->getDelegate()->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $event->sheet->getDelegate()->getStyle('A1')->getAlignment()->setHorizontal('center');
            },
        ];
    }
}
