@extends('layouts.master')

@section('title')
    Laporan Omset Customer {{ tanggal_indonesia($tanggalAwal, false) }} s/d {{ tanggal_indonesia($tanggalAkhir, false) }}
@endsection

@section('breadcrumb')
    @parent
    <li class="active">Laporan Omset Customer</li>
@endsection

@push('css')
    <link rel="stylesheet" href="{{ asset('AdminLTE-2/bower_components/bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css') }}">
@endpush

@section('content')
      <div class="row">
        <div class="col-md-12">
          <div class="box">
            <div class="box-header with-border">
              <button onclick="updatePeriode()" class="btn btn-info btn-xs btn-flat"><i class="fa fa-plus-circle"></i> Ubah Periode</button>
              <a href="{{ route('laporan_customer.export_pdf', [$tanggalAwal, $tanggalAkhir]) }}" target="_blank" class="btn btn-success btn-xs btn-flat"><i class="fa fa-file-excel-o"></i> Export PDF</a>
            </div>
            <div class="box-body table-responsive">
                <table class="table table-stiped table-bordered">
                    <thead>
                        <th width="5%">No</th>
                        <th>Nama Customer</th>
                        <th>Omset</th>
                    </thead>
                </table>
            </div>
          </div>
        </div>
      </div>

@includeIf('laporan_customer.form')
@endsection

@push('scripts')
<script src="{{ asset('AdminLTE-2/bower_components/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js') }}"></script>
    <script>
        let table;

        $(function () {
            table = $('.table').DataTable({
            processing: true,
            autoWidth: false,
             ajax: {
                 url: '{{ route('laporan_customer.data', [$tanggalAwal, $tanggalAkhir]) }}',
             },
             columns: [
                {data: 'DT_RowIndex', searchable: false, sortable: false},
                {data: 'nama_customer', searchable: false, sortable: false},
                {data: 'omset', searchable: false, sortable: false},
             ],
             dom: 'Brt',
             bSort: false,
             bPaginate: false,
        });

        $('.datepicker').datepicker({
            format: 'yyyy-mm-dd',
            autoclose: true
        });
    });
        
        function updatePeriode() {
            $('#modal-form').modal('show');
        }

    </script>
@endpush