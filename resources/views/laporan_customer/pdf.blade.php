<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Omset Customer</title>

    <style>
        {!! file_get_contents(public_path('AdminLTE-2/bower_components/bootstrap/dist/css/bootstrap.min.css')) !!}
        body { font-family: sans-serif; }
        .table-striped>tbody>tr:nth-of-type(odd) {
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>
    <h3 class="text-center">Laporan Omset Customer</h3>
    <h4 class="text-center">
        Periode {{ tanggal_indonesia($awal, false) }}
        s/d
        Tanggal {{ tanggal_indonesia($akhir, false) }}
    </h4>

    <table class="table table-striped">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th>Nama Customer</th>
                <th>Omset</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $row)
                <tr>
                    <td>{!! $row['DT_RowIndex'] !!}</td>
                    <td>{!! $row['nama_customer'] !!}</td>
                    <td>{!! $row['omset'] !!}</td>
                </tr>               
            @endforeach
        </tbody>
    </table>
</body>
</html>