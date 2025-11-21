<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Penjualan;
use Symfony\Component\HttpFoundation\Response;

class ClearEmptyTransaction
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->is('transaksi*') && ! $request->is('penjualan*')) {
        if (session()->has('id_penjualan')) {
            $id = session('id_penjualan');
            $penjualan = Penjualan::with('penjualan_detail')->find($id);

            if ($penjualan && $penjualan->penjualan_detail->count() == 0) {
                $penjualan->delete();
                session()->forget('id_penjualan');
            }
        }
    }

        return $next($request);
    }
}
