<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Setting;
use Illuminate\Http\Request;
use PDF;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     * 
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('customer.index');
    }

     public function data()
    {
        $customer = Customer::orderBy('kode_customer')->get();

        return datatables()
            ->of($customer)
            ->addIndexColumn()
            ->addColumn('select_all', function($produk) {
                return '
                    <input type="checkbox" name="id_customer[]" value="'. $produk->id_customer .'">
                ';
            })
            ->addColumn('kode_customer', function($customer) {
                return '<span class="label label-success">'. $customer->kode_customer .'<span>';
            })
            ->addColumn('aksi', function ($customer) {
                return '
                    <button type="button" onclick="editForm(`'. route('customer.update', $customer->id_customer) .'`)" class="btn btn-xs btn-info btn-flat"><i class="fa fa-pencil"></i></button>
                    <button type="button" onclick="deleteData(`'. route('customer.destroy', $customer->id_customer) .'`)" class="btn btn-xs btn-danger btn-flat"><i class="fa fa-trash"></i></button>
                ';
            })
            ->rawColumns(['aksi', 'select_all', 'kode_customer'])
            ->make(true);
    }

    /**
     * Show the form for creating a new resource.
     * 
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     * 
     * @return \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $customer = Customer::latest()->first() ?? new Customer();
        $kode_customer = (int) $customer->kode_customer +1;

        $customer = new Customer();
        $customer->kode_customer = tambah_nol_didepan($kode_customer, 5);
        $customer->nama = $request->nama;
        $customer->telepon = $request->telepon;
        $customer->alamat = $request->alamat;
        $customer->save();

        return response()->json('Data berhasil disimpan', 200);
    }

    /**
     * Display the specified resource.
     *  @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $customer = Customer::find($id);

        return response()->json($customer);
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     * 
     * @return \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $customer = Customer::find($id)->update($request->all());

        return response()->json('Data berhasil disimpan', 200);
    }

    /**
     * Remove the specified resource from storage.
     * 
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $customer = Customer::find($id);
        $customer->delete();

        return response(null, 204);
    }

    public function cetakCustomer(Request $request)
    {
        $datacustomer = collect(array());
        foreach ($request->id_customer as $id) {
            $customer = Customer::find($id);
            $datacustomer[] = $customer;
        }

        $datacustomer = $datacustomer->chunk(2);
        $setting = Setting::first();

        $no  = 1;
        $pdf = PDF::loadView('customer.cetak', compact('datacustomer', 'no', 'setting'));
        $pdf->setPaper(array(0, 0, 566.93, 850.39), 'potrait');
        return $pdf->stream('customer.pdf');
        
    }
}
