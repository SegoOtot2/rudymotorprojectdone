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
       $produk = Produk::with('kategori')->orderBy('nama_produk')->get();
        $customer = Customer::orderBy('nama')->get();
        $setting = Setting::first();

        // Cek apakah ada ID transaksi di session
        if ($id_penjualan = session('id_penjualan')) {
            $penjualan = Penjualan::find($id_penjualan);

           
            if ($penjualan && $penjualan->bayar > 0) {
                
                // 1. Buat Penjualan Baru (Draft)
                $penjualanBaru = new Penjualan();
                $penjualanBaru->id_customer = $penjualan->id_customer; // Copy customer lama
                $penjualanBaru->total_item = 0; // Akan dihitung ulang nanti
                $penjualanBaru->total_harga = 0;
                $penjualanBaru->diskon = 0;
                $penjualanBaru->bayar = 0;
                $penjualanBaru->diterima = 0;
                $penjualanBaru->id_user = auth()->id();
                $penjualanBaru->save();

                // 2. Salin Detail Item dari Transaksi Lama ke Transaksi Baru
                $detailLama = PenjualanDetail::where('id_penjualan', $id_penjualan)->get();
                
                foreach ($detailLama as $item) {
                    $detailBaru = new PenjualanDetail();
                    $detailBaru->id_penjualan = $penjualanBaru->id_penjualan;
                    $detailBaru->id_produk = $item->id_produk;
                    $detailBaru->harga_jual = $item->harga_jual;
                    $detailBaru->jumlah = $item->jumlah;
                    $detailBaru->diskon = $item->diskon;
                    $detailBaru->subtotal = $item->subtotal;
                    $detailBaru->save();
                    
                    // Update total item & harga di parent penjualan baru
                    $penjualanBaru->total_item += $item->jumlah;
                    $penjualanBaru->total_harga += $item->subtotal;
                }
                
                $penjualanBaru->update(); // Simpan total yang sudah dihitung

                // 3. Ganti Session ke ID Baru
                session(['id_penjualan' => $penjualanBaru->id_penjualan]);
                
                // 4. Arahkan variabel $penjualan ke yang baru untuk view
                $penjualan = $penjualanBaru;
                $id_penjualan = $penjualanBaru->id_penjualan;
            }
            
            // Lanjut ke logika view standar...
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

        /** @var \App\Models\PenjualanDetail $item */
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
        $total = (float) $total;
        $diskon = (float) $diskon;
        $diterima = (float) $diterima; // Uang diterima tidak perlu di-round() disini

        // Hitung total bayar setelah diskon
        $bayar = $total - ($diskon / 100 * $total);
        // Penting: Rounding bayar dilakukan disini agar total bayar bersih terdefinisi
        $bayar_bersih = round($bayar); 

        // Hitung kembalian
        // Gunakan $bayar_bersih untuk perhitungan kembalian
        $kembali = ($diterima > 0) ? $diterima - $bayar_bersih : 0;
        
        // Jika kembali minus (uang diterima kurang), tetap tampilkan 0 atau nilai kembalian minus
        if ($kembali < 0) {
            // Biarkan minus, atau set ke 0 jika ingin display kembali minimal 0
            // Namun, untuk akurasi perhitungan, biarkan minus.
        }

        $data = [
            'totalrp' => format_uang($total),
            'bayar' => $bayar_bersih, // Kirim nilai bayar bersih
            'bayarrp' => format_uang($bayar_bersih),
            'terbilang' => ucwords(terbilang($bayar_bersih). ' Rupiah'),
            'kembalirp' => format_uang($kembali),
            'kembali_terbilang' => ucwords(terbilang($kembali). ' Rupiah')
        ];

        return response()->json($data);
    }

    public function updateHarga(Request $request)
    {
        $id_penjualan = $request->id_penjualan;
        $harga_type = $request->harga_type;

        // Ambil semua detail barang yang sedang ditransaksikan
        $details = PenjualanDetail::where('id_penjualan', $id_penjualan)->get();

        foreach ($details as $item) {
            $produk = Produk::find($item->id_produk);
            
            if ($produk) {
                // Tentukan harga baru berdasarkan tipe yang dipilih
                if ($harga_type == 'harga_jual_toko') {
                    $harga_baru = $produk->harga_jual_toko;
                } else {
                    $harga_baru = $produk->harga_jual;
                }

                // Update harga jual di detail
                $item->harga_jual = $harga_baru;
                
                // Hitung ulang subtotal (tetap perhitungkan diskon per item jika ada)
                $item->subtotal = ($harga_baru * $item->jumlah) - ($item->diskon / 100 * $harga_baru * $item->jumlah);
                
                $item->update();
            }
        }

        return response()->json('Data berhasil diupdate', 200);
    }
}
