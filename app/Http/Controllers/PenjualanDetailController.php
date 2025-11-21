<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\PenjualanDetail;
use App\Models\Penjualan;
use App\Models\Produk;
use App\Models\Setting;
use Illuminate\Http\Request;

class PenjualanDetailController extends Controller
{
    public function index()
    {
        $produk = Produk::orderBy('nama_produk')->get();
        $customer = Customer::orderBy('nama')->get();
        $setting = Setting::first();

        // Cek transaksi berjalan 
        if ($id_penjualan = session('id_penjualan')) {
            $penjualan = Penjualan::find($id_penjualan);
           $customerSelected = $penjualan->customer ?? new Customer();
           $diskonGlobal = $penjualan->diskon ?? 0;

            return view('penjualan_detail.index', compact('produk', 'customer', 'setting', 'id_penjualan', 'penjualan', 'customerSelected', 'diskonGlobal'));
        } else {
        if (auth()->user()->level == 1 ) {
            return redirect()->route('transaksi.baru');
        } else {
            return redirect()->route('dashboard');
        }
        }
    }

    public function data($id)
    {
        $detail = PenjualanDetail::with('produk')
        ->where('id_penjualan', $id)
        ->get();

        $data = array();
        $total = 0;
        $total_item = 0;

        foreach ($detail as $item) {
            $row = array();
            $row['kode_produk'] = '<span class="label label-success">'. $item->produk['kode_produk'] .'<span>';
            $row['nama_produk'] = $item->produk['nama_produk'];
            $row['harga_jual'] = 'Rp. '. format_uang($item->harga_jual);
            $row['jumlah'] = '<input type="number" class="form-control quantity" data-id="'. $item->id_penjualan_detail .'" value="'.
                $item->jumlah .'">';
            $row['diskon'] = '<input type="number" class="form-control diskon" data-id="'. $item->id_penjualan_detail .'" value="'.
                $item->diskon .'">';
            $row['subtotal'] = 'Rp. '. format_uang($item->subtotal);
            $row['aksi'] = '
                    <button onclick="deleteData(`'. route('transaksi.destroy', $item->id_penjualan_detail) .'`)" class="btn btn-xs btn-danger btn-flat"><i class="fa fa-trash"></i></button>
                ';
            $data[] = $row;

            $total += $item->subtotal;
            $total_item += $item->jumlah;
        }
        $data[] = [
            'kode_produk' =>'<div class="total hide">'. $total .'</div>
                            <div class="total_item hide">'. $total_item .'</div>',
            'nama_produk' => '',
            'harga_jual' => '',
            'jumlah' => '', 
            'diskon' => '',
            'subtotal' => '', 
            'aksi' => '',
        ];

        return datatables()
            ->of($data)
            ->addIndexColumn()
            ->rawColumns(['aksi', 'kode_produk', 'jumlah','diskon'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $produk = Produk::where('id_produk', $request->id_produk)->first();
        if (! $produk) {
           return response()->json('Data gagal disimpan', 400);
        }

        if ($produk->stok <= 0) {
        return response()->json(['message' => 'Stok produk habis, tidak bisa dijual'], 422);
        }

        // Cek apakah produk sudah ada di detail penjualan ini
        $detail = PenjualanDetail::where('id_penjualan', $request->id_penjualan)
        ->where('id_produk', $produk->id_produk)
        ->first();
        
       if ($detail) {
        // Kalau sudah ada, tambahin jumlahnya
        $detail->jumlah += 1;
        $detail->subtotal = ($detail->harga_jual * $detail->jumlah) 
            - ($detail->diskon / 100 * $detail->harga_jual * $detail->jumlah);
        $detail->update();
        } else {
        // Kalau belum ada, buat baru
        $detail = new PenjualanDetail();
        $detail->id_penjualan = $request->id_penjualan;
        $detail->id_produk = $produk->id_produk;
        $harga_jual = ($request->harga_type == 'harga_jual_toko') ? $produk->harga_jual_toko : $produk->harga_jual;
        $detail->harga_jual = $harga_jual;
        $detail->jumlah = 1;
        $detail->diskon = 0;
        $detail->subtotal = ($harga_jual * $detail->jumlah) - ($detail->diskon / 100 * $harga_jual * $detail->jumlah);
        $detail->save();
    }

        return response()->json('Data berhasil disimpan', 200);
    }

    public function update (Request $request, $id) 
    {
        $detail = PenjualanDetail::find($id);
        $produk = Produk::find($detail->id_produk);

        if ($request->jumlah > $produk->stok) {
        return response()->json(['message' => 'Jumlah melebihi stok tersedia'], 422);
        }
        $detail = PenjualanDetail::find($id);
        $detail->jumlah = $request->jumlah;
        $detail->diskon = $request->diskon  ?? $detail->diskon;
        $detail->subtotal = ($detail->harga_jual * $detail->jumlah) - ($detail->diskon / 100 * $detail->harga_jual * $detail->jumlah);
        $detail->update();
        
        return response()->json(['message' => 'Data berhasil diupdate'], 200);
    }

    public function destroy ($id)
    {
        $detail = PenjualanDetail::find($id);
        $detail->delete();

        return response(null, 204);
    }

    public function loadForm($diskon = 0, $total = 0, $diterima = 0)
    {
        $bayar = $total - ($diskon / 100 * $total);
        $bayar = round($bayar);

        $diterima_bulat = round($diterima);

        $kembali = ($diterima_bulat != 0) ? $diterima_bulat - $bayar : 0;
        $data = [
            'totalrp' => format_uang($total),
            'bayar' => $bayar,
            'bayarrp' => format_uang($bayar),
            'terbilang' => ucwords(terbilang($bayar). ' Rupiah'),
            'kembalirp' => format_uang($kembali),
            'kembali_terbilang' => ucwords(terbilang($kembali). ' Rupiah')
        ];

        return response()->json($data);
    }
}
