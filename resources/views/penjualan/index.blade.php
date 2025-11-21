@extends('layouts.master')

@section('title')
    Daftar Penjualan
@endsection

@section('breadcrumb')
    @parent
    <li class="active">Daftar Penjualan</li>
@endsection

@section('content')
      <div class="row">
        <div class="col-md-12">
          <div class="box">
            <div class="box-body table-responsive">
                <table class="table table-stiped table-bordered table-penjualan">
                    <thead>
                        <th width="5%">No</th>
                        <th>Tanggal</th>
                        <th>Customer</th>
                        <th>Total Item</th>
                        <th>Total Harga</th>
                        <th>Diskon Nota</th>
                        <th>Total Bayar</th>
                        <th>Toko</th>
                        <th>Kasir</th>
                        <th width="15%"><i class="fa fa-cog"></i></th>
                    </thead>
                </table>
            </div>
          </div>
        </div>
      </div>

@includeIf('penjualan.detail')
@endsection

@push('scripts')
    <script>
        let table, table1;

        $(function () {
            table = $('.table-penjualan').DataTable({
            processing: true,
            autoWidth: false,
             ajax: {
                 url: '{{ route('penjualan.data') }}',
             },
             columns: [
                {data: 'DT_RowIndex', searchable: false, sortable: false},
                {data: 'tanggal'},
                {data: 'customer'},
                {data: 'total_item'},
                {data: 'total_harga'},
                {data: 'diskon'},
                {data: 'bayar'},
                {data: 'nama_toko'},
                {data: 'kasir'},
                {data: 'aksi', searchable: false, sortable: false},
             ]
        });

        table1 = $('.table-detail').DataTable({
            processing: true,
            bsort: false,
            dom: 'Brt',
            columns: [
                {data: 'DT_RowIndex', searchable: false, sortable: false},
                {data: 'kode_produk'},
                {data: 'nama_produk'},
                {data: 'harga_jual'},
                {data: 'jumlah'},
                {data: 'diskon'},
                {data: 'subtotal'},
             ]
        })
    });
        

        function showDetail(url) {
            $('#modal-detail').modal('show');

            table1.ajax.url(url);
            table1.ajax.reload();
        }


        function deleteData(url) {
           if(confirm('Yakin ingin menghapus data?')){
             $.post(url, {
                '_token': $('[name=csrf-token]').attr('content'),
                '_method': 'delete'
            })
            .done((response) => {
                table.ajax.reload();
            })
            .fail((errors) => {
                alert('Tidak dapat menghapus data');
                return;
            })
           }
        }

    </script>
@endpush