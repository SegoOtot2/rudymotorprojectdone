<?php

namespace App\Http\Controllers;

use App\Models\Penjualan;
use App\Models\PenjualanDetail;
use App\Models\Produk;
use App\Models\Setting;
use Illuminate\Http\Request;
use PDF;

class PenjualanController extends Controller
{

    public function index()
    {
        return view('penjualan.index');
    }

    public function data()
    {
         $penjualan = Penjualan::where('total_item', '>', 0)
                ->orderBy('id_penjualan', 'desc')
                        ->get();

        return datatables()
            ->of($penjualan)
            ->addIndexColumn()
            ->addColumn('total_item', function ($penjualan) {
                return format_uang($penjualan->total_item);
            })
            ->addColumn('total_harga', function ($penjualan) {
                return 'Rp. '. format_uang($penjualan->total_harga);
            })
            ->addColumn('bayar', function ($penjualan) {
                return 'Rp. '. format_uang($penjualan->bayar);
            })
            ->addColumn('tanggal', function ($penjualan) {
                return tanggal_indonesia($penjualan->created_at, false);
            })
            ->addColumn('customer', function ($penjualan) {
                return $penjualan->customer->nama ?? '';
            })
            ->editColumn('diskon', function ($penjualan) {
                return $penjualan->diskon . ' %';
            })
            ->editColumn('kasir', function ($penjualan) {
                return $penjualan->user->name ?? '';
            })
            ->addColumn('aksi', function ($penjualan) {
                return '
                    <button onclick="showDetail(`'. route('penjualan.show', $penjualan->id_penjualan) .'`)" class="btn btn-xs btn-info btn-flat">
                        <i class="fa fa-eye"></i> Detail
                    </button>
                    <a href="'. route('penjualan.nota', $penjualan->id_penjualan) .'" target="_blank" class="btn btn-xs btn-success btn-flat">
                        <i class="fa fa-print"></i> Print
                    </a>
                    <button onclick="deleteData(`'. route('penjualan.destroy', $penjualan->id_penjualan) .'`)" class="btn btn-xs btn-danger btn-flat">
                        <i class="fa fa-trash"></i> Hapus
                    </button>
                    ';
            })
            ->rawColumns(['aksi'])
            ->make(true);
    }


    public function create()
    {
        $penjualan = new Penjualan();
        $penjualan->id_customer = null;
        $penjualan->total_item = 0;
        $penjualan->total_harga = 0;
        $penjualan->diskon = 0;
        $penjualan->bayar = 0;
        $penjualan->diterima = 0;
        $penjualan->id_user = auth()->id();
        $penjualan->save();

        session(['id_penjualan' => $penjualan->id_penjualan]);
        return redirect()->route('transaksi.index');
    }

    public function store(Request $request) 
    {
        $penjualan = Penjualan::findOrFail($request->id_penjualan);
        $penjualan->id_customer = $request->id_customer;
        $penjualan->total_item = $request->total_item;
        $penjualan->total_harga = $request->total;
        $penjualan->diskon = $request->diskon;
        $penjualan->bayar = $request->bayar;
        $penjualan->diterima = $request->diterima;
        $penjualan->update();

        $detail = PenjualanDetail::where('id_penjualan', $penjualan->id_penjualan)->get();
        foreach ($detail as $item) {
            $produk = Produk::find($item->id_produk);
            $produk->stok -= $item->jumlah;
            $produk->update();
        }

        return redirect()->route('transaksi.selesai');
    }

    public function show($id)
    {
        $detail = PenjualanDetail::with('produk')->where('id_penjualan', $id)->get();

        return datatables()
            ->of($detail)
            ->addIndexColumn()
            ->addColumn('kode_produk', function ($detail) {
                return '<span class="label label-success">'. $detail->produk->kode_produk .'</span>';
            })
            ->addColumn('nama_produk', function ($detail) {
                return $detail->produk->nama_produk;
            })
            ->addColumn('harga_jual', function ($detail) {
                return 'Rp. '. format_uang($detail->harga_jual);
            })
            ->addColumn('jumlah', function ($detail) {
                return  format_uang($detail->jumlah);
            })
            ->addColumn('subtotal', function ($detail) {
                return 'Rp. '. format_uang($detail->subtotal);
            })
            ->rawColumns(['kode_produk'])
            ->make(true);
    }

    public function destroy($id)
    {
        $penjualan = Penjualan::find($id);
        $detail    = PenjualanDetail::where('id_penjualan', $penjualan->id_penjualan)->get();
        foreach ($detail as $item) {
            $produk = Produk::find($item->id_produk);
            if ($produk) {
                $produk->stok += $item->jumlah;
                $produk->update();
            }

            $item->delete();
        }

        $penjualan->delete();

        return response(null, 204);
    }

    public function selesai() {
        $setting = Setting::first();
        return view('penjualan.selesai', compact('setting'));
    }

    public function notaBesar() {
        $setting = Setting::first();
        $penjualan = Penjualan::find(session('id_penjualan'));
        if (! $penjualan) {
            abort(404);
        }
        $detail = PenjualanDetail::with('produk')
            ->where('id_penjualan', session('id_penjualan'))
            ->get();

        // LOGIKA SWITCH TIPE NOTA
        if ($setting->tipe_nota == 1) {
            // TIPE 1: NOTA BESAR (A4)
            $pdf = PDF::loadView('penjualan.nota_besar', compact('setting', 'penjualan', 'detail'));
            $pdf->setPaper('a4', 'portrait');
        } else {
            // TIPE 2: NOTA KECIL (LX-310 / Struk)
            $pdf = PDF::loadView('penjualan.nota_dot', compact('setting', 'penjualan', 'detail'));
            $pdf->setPaper([0, 0, 609.4488, 396.85], 'portrait');
        }
        
        return $pdf->stream('Transaksi-'. date('Y-m-d-his') .'.pdf');
    }

    // GANTI method cetakNota (untuk cetak ulang dari riwayat)
    public function cetakNota($id)
    {
        $setting = Setting::first();
        $penjualan = Penjualan::findOrFail($id);
        $detail = PenjualanDetail::with('produk')
            ->where('id_penjualan', $id)
            ->get();

        // LOGIKA SWITCH TIPE NOTA
        if ($setting->tipe_nota == 1) {
            $pdf = PDF::loadView('penjualan.nota_besar', compact('setting', 'penjualan', 'detail'));
            $pdf->setPaper('A4', 'portrait'); 
        } else {
            $pdf = PDF::loadView('penjualan.nota_dot', compact('setting', 'penjualan', 'detail'));
            $pdf->setPaper([0, 0, 609.4488, 396.85], 'portrait');
        }

        return $pdf->stream('Nota-'. date('Y-m-d-his') .'.pdf');
    }
}
