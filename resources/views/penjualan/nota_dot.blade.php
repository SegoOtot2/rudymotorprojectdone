<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota DOT</title>

    <style>
        /* 1. SETUP UKURAN KERTAS DI CSS */
        @media print {
            @page {
                /* Mengunci ukuran kertas agar browser tidak bingung */
                size: 9.5in 6.5in; 
                margin: 0; /* Nol-kan margin browser */
            }
            body {
                /* Margin fisik untuk konten agar tidak kena lubang kertas (tractor feed) */
                /* Atas: 5mm, Kanan: 10mm, Bawah: 5mm, Kiri: 10mm */
                margin: 5mm 10mm 5mm 10mm; 
            }
        }

        body {
            font-family: "Courier New", Courier, monospace;
            font-size: 10pt; /* Ukuran font standar LX-310 */
            line-height: 1.1; /* Jarak antar baris dirapatkan agar muat banyak */
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        /* 2. GARIS VERTIKAL & HORIZONTAL (DASHED) */
        table.data th,
        table.data td {
            border: 1px dashed #000; /* Garis putus-putus keliling */
            padding: 2px 4px; /* Padding DITIPISKAN (2px) agar hemat tempat vertikal */
            vertical-align: middle;
        }

        table.data th {
            text-align: center;
            font-weight: bold;
            padding-top: 5px;
            padding-bottom: 5px;
        }

        /* Utility Classes */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .bold { font-weight: bold; }

        /* Bagian Tanda Tangan (Tanpa garis kotak) */
        .signature-section {
            margin-top: 10px; /* Jarak dari tabel data */
        }
    </style>
</head>
<body>
    <table width="100%" style="margin-bottom: 5px;">
        <tr>
            <td rowspan="3" width="60%" style="vertical-align: top; border: none; padding: 0;">
                <div style="font-size: 12pt; font-weight: bold;">{{ $setting->nama_perusahaan ?? 'NAMA TOKO' }}</div>
                <div style="font-size: 9pt;">{{ $setting->alamat }}</div>
            </td>
            <td width="15%" style="border: none; padding: 0; font-size: 9pt;">Tanggal</td>
            <td width="25%" style="border: none; padding: 0; font-size: 9pt;">: {{ date('d/m/Y', strtotime($penjualan->created_at)) }}</td>
        </tr>
        <tr>
            <td style="border: none; padding: 0; font-size: 9pt;">Customer</td>
            <td style="border: none; padding: 0; font-size: 9pt;">: {{ $penjualan->customer->nama ?? 'Umum' }}</td>
        </tr>
    </table>

    <table class="data" width="100%">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th>Nama Barang</th>
                <th width="15%">Harga</th>
                <th width="8%">Qty</th>
                <th width="8%">Disc</th>
                <th width="18%">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($detail as $key => $item)
                <tr>
                    <td class="text-center">{{ $key+1 }}</td>
                    <td class="text-left">{{ $item->produk->nama_produk }}</td>
                    <td class="text-right">{{ format_uang($item->harga_jual) }}</td>
                    <td class="text-center">{{ format_uang($item->jumlah) }}</td>
                    <td class="text-center">{{ $item->diskon > 0 ? $item->diskon.'%' : '0%' }}</td>
                    <td class="text-right">{{ format_uang($item->subtotal) }}</td>
                </tr>
            @endforeach
            
            </tbody>
        <tfoot>
            <tr>
                <td colspan="5" class="text-right bold">Total</td>
                <td class="text-right bold">{{ format_uang($penjualan->total_harga) }}</td>
            </tr>
            @if($penjualan->diskon > 0)
            <tr>
                <td colspan="5" class="text-right bold">Diskon Akhir</td>
                <td class="text-right bold">{{ format_uang($penjualan->diskon) }} %</td>
            </tr>
            @endif
            <tr>
                <td colspan="5" class="text-right bold" style="border-top: 1px dashed #000;">Grand Total</td>
                <td class="text-right bold" style="border-top: 1px dashed #000;">{{ format_uang($penjualan->bayar) }}</td>
            </tr>
        </tfoot>
    </table>

    <table class="signature-section" width="100%" style="border: none;">
        <tr>
            <td class="text-center" width="30%" style="border: none;">
                Hormat Kami,<br><br><br>
                ( {{ auth()->user()->name }} )
            </td>
            <td width="40%" style="border: none;"></td> <td class="text-center" width="30%" style="border: none;">
                Penerima,<br><br><br>
                ( {{ $penjualan->customer->nama ?? '................' }} )
            </td>
        </tr>
    </table>
</body>
</html>