<?php

namespace App\DataTables;

use App\Models\Transaksi;
use App\Models\User;
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
            ->addColumn('items_list', function ($row) {
                $items = json_decode($row->items_data, true);
                if (!$items) {
                    return '-';
                }

                $html = '<ul class="mb-0">';
                foreach ($items as $item) {
                    $html .= '<li>'
                        . e($item['nama'])
                        . ' (' . $item['quantity'] . ' x Rp ' . number_format($item['harga'], 0, ',', '.') . ')'
                        . '</li>';
                }
                $html .= '</ul>';

                return $html;
            })

            ->addColumn('tanggal_transaksi_formatted', function ($row) {
                return date('d/m/Y H:i', strtotime($row->tanggal_transaksi));
            })
            ->addColumn('total_formatted', function ($row) {
                return 'Rp ' . number_format($row->total, 0, ',', '.');
            })
            ->addColumn('status_badge', function ($row) {
                if ($row->status_pembayaran === 'berhasil') {
                    return '<span class="badge bg-success">Berhasil</span>';
                } elseif ($row->status_pembayaran === 'pending') {
                    return '<span class="badge bg-warning text-dark">Pending</span>';
                } elseif ($row->status_pembayaran === 'expired') {
                    return '<span class="badge bg-red text-dark">Expired</span>';
                } else {
                    return '<span class="badge bg-danger">Gagal</span>';
                }
            })
            ->addColumn('action', function ($row) {
                return view('admin.transaksi.action', compact('row'));
            })
            ->rawColumns(['status_badge', 'action', 'items_list'])

            ->setRowId('id');
    }

    public function query(Transaksi $model): QueryBuilder
    {
        return $model->newQuery()
            ->leftJoin('tb_users', 'tb_transaksi.user_id', '=', 'tb_users.id')
            ->select('tb_transaksi.*', 'tb_users.nama as user_name')
            ->orderBy('tb_transaksi.created_at', 'desc');
    }
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('admintransaction-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom('Bfrtip')
            ->orderBy(1)
            ->selectStyleSingle()
            ->pageLength(10)
            ->lengthMenu([10, 25, 50, 100])
            ->buttons([
                [
                    'extend' => 'excel',
                    'text' => '<i class="fas fa-file-excel"></i> Excel',
                    'className' => 'btn btn-success btn-sm me-2',
                    'exportOptions' => ['columns' => [0, 1, 2, 3, 4, 5, 6]]
                ],
                [
                    'extend' => 'pdf',
                    'text' => '<i class="fas fa-file-pdf"></i> PDF',
                    'className' => 'btn btn-danger btn-sm me-2',
                    'exportOptions' => ['columns' => [0, 1, 2, 3, 4, 5, 6]]
                ],
                [
                    'text' => '<i class="fas fa-sync-alt"></i> Reload',
                    'className' => 'btn btn-primary btn-sm',
                    'action' => 'function ( e, dt, node, config ) { dt.ajax.reload(); }',
                ],
            ])
            ->parameters([
                'responsive' => true,
                'autoWidth' => false,
                'language' => ['url' => '//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json']
            ]);
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

            Column::computed('tanggal_transaksi_formatted')
                ->title('Tanggal')
                ->width(150),

            Column::computed('total_formatted')
                ->title('Total')
                ->addClass('text-end')
                ->width(120),

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
