<?php

namespace App\DataTables;

use App\Models\AdminKategoriSparepart;
use App\Models\KategoriSparepart;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class AdminKategoriSparepartDataTable extends DataTable
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
            ->addColumn('jumlah_sparepart', function ($row) {
                $count = $row->spareparts_count ?? $row->spareparts()->count();
                return '<span class="badge bg-info">' . $count . ' Item</span>';
            })
            ->addColumn('deskripsi_singkat', function ($row) {
                if ($row->deskripsi) {
                    return strlen($row->deskripsi) > 50
                        ? substr($row->deskripsi, 0, 50) . '...'
                        : $row->deskripsi;
                } else {
                    return '<span class="text-muted">-</span>';
                }
            })
            ->addColumn('action', function ($row) {
                return view('admin.kategori-sparepart.action', compact('row'));
            })
            ->rawColumns(['jumlah_sparepart', 'deskripsi_singkat', 'status_kategori', 'action'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(KategoriSparepart $model): QueryBuilder
    {
        return $model->newQuery()->withCount('spareparts');
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('kategori-sparepart-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom('Bfrtip')
            ->scrollX(true)
            ->orderBy(1)
            ->selectStyleSingle()
            ->pageLength(25)
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

            Column::make('nama_kategori')
                ->title('Nama Kategori'),

            Column::computed('deskripsi_singkat')
                ->title('Deskripsi')
                ->orderable(false),

            Column::computed('jumlah_sparepart')
                ->title('Jumlah Item')
                ->searchable(false)
                ->orderable(true)
                ->width(120)
                ->addClass('text-center'),

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
        return 'KategoriSparepart_' . date('YmdHis');
    }
}
