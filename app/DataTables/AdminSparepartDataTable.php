<?php

namespace App\DataTables;

use App\Models\Sparepart;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class AdminSparepartDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->addColumn('gambar', function ($row) {
                if ($row->gambar_produk && file_exists(public_path('storage/' . $row->gambar_produk))) {
                    return '<img src="' . asset('storage/' . $row->gambar_produk) . '" 
                            class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">';
                } else {
                    return '<div class="bg-light d-flex align-items-center justify-content-center" 
                            style="width: 50px; height: 50px; border-radius: 4px;">
                            <i class="fas fa-image text-muted"></i>
                        </div>';
                }
            })
            ->addColumn('kategori_nama', function ($row) {
                return $row->kategori ? $row->kategori->nama_kategori : '-';
            })
            ->addColumn('harga_formatted', function ($row) {
                return 'Rp ' . number_format($row->harga, 0, ',', '.');
            })
            ->addColumn('stok_badge', function ($row) {
                if ($row->stok <= 0) {
                    return '<span class="badge bg-danger">Habis (0)</span>';
                } elseif ($row->stok <= 10) {
                    return '<span class="badge bg-warning text-dark">Terbatas (' . $row->stok . ')</span>';
                } else {
                    return '<span class="badge bg-success">Tersedia (' . $row->stok . ')</span>';
                }
            })
            ->addColumn('action', function ($row) {
                return view('admin.sparepart.action', compact('row'));
            })
            ->rawColumns(['gambar', 'stok_badge', 'action'])
            ->setRowId('kode_sparepart');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(Sparepart $model): QueryBuilder
    {
        return $model->newQuery()->with('kategori');
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('sparepart-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom('Bfrtip')
            ->scrollX(true)
            ->orderBy(1)
            ->selectStyleSingle()
            ->pageLength(10)
            ->lengthMenu([10, 25, 50, 100])
            ->buttons([
                [
                    'text' => '<i class="fas fa-sync-alt"></i> Reload',
                    'className' => 'btn btn-primary btn-sm',
                    'action' => 'function ( e, dt, node, config ) { dt.ajax.reload(); }',
                ],
            ])
            ->parameters([
                'responsive' => true,
                'autoWidth' => false,
                'language' => [
                    'url' => '//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json'
                ]
            ]);
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')
                ->title('No')
                ->searchable(false)
                ->orderable(false)
                ->width(50)
                ->addClass('text-center'),

            Column::make('kode_sparepart')
                ->title('Kode')
                ->width(100),

            Column::computed('gambar')
                ->title('Gambar')
                ->searchable(false)
                ->orderable(false)
                ->width(80)
                ->addClass('text-center'),

            Column::make('nama_sparepart')
                ->title('Nama Sparepart'),

            Column::make('merk')
                ->title('Merk')
                ->width(100),

            Column::computed('stok_badge')
                ->title('Stok')
                ->searchable(false)
                ->orderable(true)
                ->width(100)
                ->addClass('text-center'),

            Column::computed('harga_formatted')
                ->title('Harga')
                ->searchable(false)
                ->orderable(true)
                ->width(120)
                ->addClass('text-end'),


            Column::computed('kategori_nama')
                ->title('Kategori')
                ->width(120),

            Column::computed('action')
                ->title('Aksi')
                ->exportable(false)
                ->printable(false)
                ->width(120)
                ->addClass('text-center'),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Sparepart_' . date('YmdHis');
    }
}
