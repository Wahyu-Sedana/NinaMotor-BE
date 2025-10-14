<?php

namespace App\DataTables;

use App\Models\Transaksi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class AdminTransactionDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->addColumn('user_nama', function ($row) {
                return $row->user ? $row->user->nama : '-';
            })
            ->addColumn('tanggal_transaksi_formatted', function ($row) {
                return Carbon::parse($row->tanggal_transaksi)
                    ->locale('id')
                    ->settings(['formatFunction' => 'translatedFormat'])
                    ->translatedFormat('d F Y H:i');
            })
            ->addColumn('total_formatted', function ($row) {
                return 'Rp ' . number_format($row->total, 0, ',', '.');
            })
            ->addColumn('total_ongkir', function ($row) {
                return 'Rp ' . number_format($row->ongkir ?? 0, 0, ',', '.');
            })
            ->addColumn('type_pembelian_badge', function ($row) {
                if ($row->type_pembelian == 0) {
                    return '<span class="badge bg-primary">Sparepart</span>';
                } elseif ($row->type_pembelian == 1) {
                    return '<span class="badge bg-info">Servis Motor</span>';
                } else {
                    return '<span class="badge bg-secondary">-</span>';
                }
            })
            ->addColumn('nama_penerima', function ($row) {
                if ($row->alamatPengiriman) {
                    return $row->alamatPengiriman->nama_penerima;
                }
                return '-';
            })
            // ->addColumn('no_telp_penerima', function ($row) {
            //     if ($row->alamatPengiriman) {
            //         return $row->alamatPengiriman->no_telp_penerima;
            //     }
            //     return '-';
            // })
            ->addColumn('alamat_lengkap', function ($row) {
                if ($row->alamatPengiriman) {
                    $alamat = $row->alamatPengiriman;
                    return $alamat->alamat_lengkap . ', '
                        . $alamat->district_name . ', '
                        . $alamat->city_name . ', '
                        . $alamat->province_name . ' '
                        . ($alamat->kode_pos ?? '');
                }
                return '-';
            })
            ->addColumn('kurir_info', function ($row) {
                if ($row->kurir && $row->service) {
                    return '<div>
                        <strong>' . strtoupper($row->kurir) . '</strong> - ' . $row->service . '<br>
                        <small class="text-muted">Estimasi: ' . ($row->estimasi ?? '-') . ' hari</small>
                    </div>';
                }
                return '<span class="text-muted">-</span>';
            })
            ->addColumn('status_badge', function ($row) {
                if ($row->status_pembayaran === 'berhasil') {
                    return '<span class="badge bg-success">Berhasil</span>';
                } elseif ($row->status_pembayaran === 'pending') {
                    return '<span class="badge bg-warning text-dark">Pending</span>';
                } elseif ($row->status_pembayaran === 'expired') {
                    return '<span class="badge bg-danger text-white">Expired</span>';
                } else {
                    return '<span class="badge bg-danger">Gagal</span>';
                }
            })
            ->addColumn('action', function ($row) {
                return view('admin.transaksi.action', compact('row'));
            })
            ->rawColumns(['status_badge', 'action', 'type_pembelian_badge', 'alamat_info', 'kurir_info'])
            ->setRowId('id');
    }

    public function query(Transaksi $model): QueryBuilder
    {
        $query = $model->newQuery()
            ->leftJoin('tb_users', 'tb_transaksi.user_id', '=', 'tb_users.id')
            ->with(['alamatPengiriman']) // Eager load alamat
            ->select('tb_transaksi.*', 'tb_users.nama as user_name')
            ->orderBy('tb_transaksi.created_at', 'desc');

        if ($tahun = request('tahun')) {
            $query->whereYear('tb_transaksi.tanggal_transaksi', $tahun);
        }

        if ($bulan = request('bulan')) {
            $query->whereMonth('tb_transaksi.tanggal_transaksi', $bulan);
        }

        if ($status = request('status')) {
            $query->where('tb_transaksi.status_pembayaran', $status);
        }

        // Filter by type pembelian
        if ($type = request('type_pembelian')) {
            $query->where('tb_transaksi.type_pembelian', $type);
        }

        if ($search = request('search_custom')) {
            $query->where(function ($q) use ($search) {
                $q->where('tb_users.nama', 'like', "%{$search}%")
                    ->orWhere('tb_transaksi.metode_pembayaran', 'like', "%{$search}%")
                    ->orWhere('tb_transaksi.kurir', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('admintransaction-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom('t<"d-flex justify-content-between align-items-center"lip>')
            ->scrollX(true)
            ->orderBy(1);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')
                ->title('No')
                ->searchable(false)
                ->orderable(false)
                ->width(50)
                ->addClass('text-center'),

            Column::make('id')
                ->title('ID Transaksi')
                ->width(150),

            Column::computed('user_nama')
                ->title('Nama Customer')
                ->width(150),

            Column::computed('type_pembelian_badge')
                ->title('Tipe')
                ->width(100)
                ->addClass('text-center'),

            Column::computed('tanggal_transaksi_formatted')
                ->title('Tanggal')
                ->width(150),

            Column::computed('total_formatted')
                ->title('Total')
                ->addClass('text-end')
                ->width(120),

            Column::computed('total_ongkir')
                ->title('Ongkir')
                ->addClass('text-end')
                ->width(100),

            Column::computed('kurir_info')
                ->title('Kurir & Estimasi')
                ->width(150),

            Column::computed('nama_penerima')
                ->title('Penerima')
                ->width(150),

            // Column::computed('no_telp_penerima')
            //     ->title('No. Telepon')
            //     ->width(120),

            Column::computed('alamat_lengkap')
                ->title('Alamat Lengkap')
                ->width(250)
                ->orderable(false),

            Column::make('metode_pembayaran')
                ->title('Metode Pembayaran')
                ->width(150),

            Column::computed('status_badge')
                ->title('Status Pembayaran')
                ->width(150)
                ->searchable(false)
                ->addClass('text-center'),

            Column::computed('action')
                ->title('Aksi')
                ->exportable(false)
                ->printable(false)
                ->width(120)
                ->addClass('text-center'),
        ];
    }

    protected function filename(): string
    {
        return 'AdminTransaction_' . date('YmdHis');
    }
}
