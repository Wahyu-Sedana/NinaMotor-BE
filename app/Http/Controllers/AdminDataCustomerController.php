<?php

namespace App\Http\Controllers;

use App\DataTables\AdminDataCustomerDataTable;
use Illuminate\Http\Request;

class AdminDataCustomerController extends Controller
{
    public function index(AdminDataCustomerDataTable $dataTable)
    {
        return $dataTable->render('admin.customer.index', [
            'title' => 'Customer'
        ]);
    }

    public function create()
    {
        return view('admin.customer.create', [
            'title' => 'Tambah Customer'
        ]);
    }
}
