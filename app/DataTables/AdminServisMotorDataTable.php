<?php

namespace App\DataTables;

use App\Models\AdminServisMotor;
use App\Models\ServisMotor;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class AdminServisMotorDataTable extends DataTable
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
            ->addColumn('nama_user', function ($row) {
                return $row->user ? $row->user->nama : '-';
            })
            ->addColumn('tanggal_dibuat', function ($row) {
                return Carbon::parse($row->created_at)
                    ->locale('id')
                    ->settings(['formatFunction' => 'translatedFormat'])
                    ->translatedFormat('d F Y H:i');
            })

            ->addColumn('status_badge', function ($row) {
                switch ($row->status) {
                    case 'pending':
                        return '<span class="badge bg-warning text-dark">Pending</span>';
                    case 'in_service':
                        return '<span class="badge bg-primary">Proses</span>';
                    case 'priced':
                        return '<span class="badge bg-info">Pembayaran</span>';
                    case 'done':
                        return '<span class="badge bg-success">Selesai</span>';
                    default:
                        return '<span class="badge bg-secondary">Tidak Diketahui</span>';
                }
            })
            ->addColumn('action', function ($row) {
                return view('admin.servis.action', compact('row'));
            })
            ->rawColumns(['status_badge', 'action'])
            ->setRowId('id');
    }

    /**
     * Query source of dataTable.
     */
    public function query(ServisMotor $model): QueryBuilder
    {
        $query = $model->newQuery()->with('user')->orderBy('created_at', 'desc');

        if ($tahun = request('tahun')) {
            $query->whereYear('created_at', $tahun);
        }

        if ($bulan = request('bulan')) {
            $query->whereMonth('created_at', $bulan);
        }

        if ($search = request('search_custom')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($uq) use ($search) {
                    $uq->where('nama', 'like', "%{$search}%");
                })
                    ->orWhere('no_kendaraan', 'like', "%{$search}%")
                    ->orWhere('jenis_motor', 'like', "%{$search}%")
                    ->orWhere('keluhan', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('servismotor-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom('t<"d-flex justify-content-between align-items-center"lip>')
            ->scrollX(true)
            ->orderBy(1);
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

            Column::computed('nama_user')
                ->title('Pelanggan')
                ->width(150),

            Column::make('no_kendaraan')
                ->title('No Kendaraan')
                ->width(120),

            Column::make('jenis_motor')
                ->title('Jenis Motor')
                ->width(120),

            Column::make('keluhan')
                ->title('Keluhan'),

            Column::computed('status_badge')
                ->title('Status')
                ->searchable(false)
                ->orderable(true)
                ->width(100)
                ->addClass('text-center'),


            Column::make('tanggal_dibuat')
                ->title('Tanggal Input')
                ->width(150),

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
        return 'ServisMotor_' . date('YmdHis');
    }
}
