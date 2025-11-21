<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Kategori;
use App\Models\Pembelian;
use App\Models\Pengeluaran;
use App\Models\Penjualan;
use App\Models\Produk;
use App\Models\Supplier;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $kategori = Kategori::count();
        $produk = Produk::count();
        $supplier = Supplier::count();
        $customer = Customer::count();
        $total_item_terjual = Penjualan::sum('total_item');
        
        $bulanIni = date('m');
        $tahunIni = date('Y');

        $transaksi_customer = Customer::withSum(['penjualan' => function ($query) use ($bulanIni, $tahunIni) {
            $query->whereMonth('created_at', $bulanIni)
                  ->whereYear('created_at', $tahunIni);
        }], 'bayar')->get();

        $tanggal_awal = date('Y-m-01');
        $tanggal_akhir = date('Y-m-d');
        
        $data_tanggal = array();
        $data_pendapatan = array();

        while (strtotime($tanggal_awal) <= strtotime($tanggal_akhir)) {
            $data_tanggal[] = (int) substr($tanggal_awal, 8, 2);

            $total_penjualan = Penjualan::where('created_at', 'LIKE', "%$tanggal_awal%")->sum('bayar');
            $total_pembelian = Pembelian::where('created_at', 'LIKE', "%$tanggal_awal%")->sum('bayar');
            $total_pengeluaran = Pengeluaran::where('created_at', 'LIKE', "%$tanggal_awal%")->sum('nominal');

            $pendapatan = $total_penjualan - $total_pembelian - $total_pengeluaran;
            $data_pendapatan[] = $pendapatan;

            $tanggal_awal = date('Y-m-d', strtotime("+1 day", strtotime($tanggal_awal)));
        }
        
        $tanggal_awal = date('Y-m-01');

        if (auth()->user()->level == 1) {
            return view('admin.dashboard', compact('kategori', 'produk', 'supplier', 'customer', 'tanggal_awal', 'tanggal_akhir', 'data_tanggal', 'data_pendapatan', 'total_item_terjual','transaksi_customer'));
        } else {
            return view('kasir.dashboard');
        }   
    }
}
