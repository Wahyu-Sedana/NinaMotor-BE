<?php

namespace App\DataTables;

use App\Models\AdminDataCustomer;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class AdminDataCustomerDataTable extends DataTable
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
            ->editColumn('nama', function ($row) {
                return $row->nama ?? '-';
            })
            ->editColumn('email', function ($row) {
                return $row->email ?? '-';
            })
            ->editColumn('no_telp', function ($row) {
                return $row->no_telp ?? '-';
            })
            ->addColumn('action', function ($row) {
                return view('admin.customer.action', compact('row'));
            })
            ->rawColumns(['action'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(User $model): QueryBuilder
    {
        return $model->newQuery()
            ->where('role', 'customer');
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('admindatacustomer-table')
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

            Column::make('nama')
                ->title('Nama Customer'),

            Column::computed('email')
                ->title('Email')
                ->orderable(false),

            Column::computed('no_telp')
                ->title('No Telp')
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
        return 'AdminDataCustomer_' . date('YmdHis');
    }
}
