<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Penjualan;
use Illuminate\Support\Facades\DB; // PASTIKAN TAMBAHKAN INI
use PDF;

class LaporanCustomerController extends Controller
{
    public function index(Request $request)
    {
        $tanggalAwal = date('Y-m-d', mktime(0, 0, 0, date('m'), 1, date('Y')));
        $tanggalAkhir = date('Y-m-d');

        if ($request->has('tanggal_awal') && $request->tanggal_awal != "" && $request->has('tanggal_akhir') && $request->tanggal_akhir) {
            $tanggalAwal = $request->tanggal_awal;
            $tanggalAkhir = $request->tanggal_akhir;
        }

        return view('laporan_customer.index', compact('tanggalAwal', 'tanggalAkhir'));
    }

    public function getData($awal, $akhir)
    {
        $no = 1;
        $data = array();
        $total_semua_omset = 0;

        // Query diubah ke LEFT JOIN
        $penjualan = Customer::leftJoin('penjualan', function($join) use ($awal, $akhir) {
                // Tambahkan kondisi rentang tanggal di dalam join
                $join->on('customer.id_customer', '=', 'penjualan.id_customer')
                     ->whereBetween('penjualan.created_at', [$awal . ' 00:00:00', $akhir . ' 23:59:59']);
            })
            ->select(
                'customer.nama as nama_customer',
                // Gunakan COALESCE untuk mengubah nilai NULL (tidak ada transaksi) menjadi 0
                DB::raw('COALESCE(SUM(penjualan.bayar), 0) as total_omset') 
            )
            ->groupBy('customer.id_customer', 'customer.nama')
            ->orderBy('total_omset', 'desc') // Tetap urutkan dari omset terbesar
            ->get();

        foreach ($penjualan as $sale) {
            $row = array();
            $row['DT_RowIndex'] = $no++;
            $row['nama_customer'] = $sale->nama_customer;
            $row['omset'] = format_uang($sale->total_omset);
            $data[] = $row;

            $total_semua_omset += $sale->total_omset;
        }

        // Tambah baris Grand Total
        $row = array();
        $row['DT_RowIndex'] = '';
        $row['nama_customer'] = '<strong>GRAND TOTAL OMSET</strong>';
        $row['omset'] = '<strong>' . format_uang($total_semua_omset) . '</strong>';
        $data[] = $row;

        return $data;
    }

    public function data($awal, $akhir)
    {
        $data = $this->getData($awal, $akhir);

        return datatables()
            ->of($data)
            ->rawColumns(['nama_customer', 'omset'])
            ->make(true);
    }

    public function exportPDF($awal, $akhir)
    {
        $data = $this->getData($awal, $akhir);
        $pdf = PDF::loadView('laporan_customer.pdf', compact('awal', 'akhir', 'data'));
        $pdf->setPaper('a4', 'potrait');
        return $pdf->stream('Laporan-Omset-Customer-' . date('Y-m-d-his') . '.pdf');
    }
}