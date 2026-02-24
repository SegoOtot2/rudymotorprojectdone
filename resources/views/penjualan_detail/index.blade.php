@extends('layouts.master')

@section('title')
    Transaksi Penjualan
@endsection

@push('css')
    <style>
        .tampil-bayar {
            font-size: 5em;
            text-align: center;
            height: 100px;
        }

        .tampil-terbilang {
            padding: 10px;
            background: #f0f0f0;
        }

        .table-penjualan tbody tr:last-child {
            display: none;
        }

        @media(max-width: 768px) {
            .tampil-bayar {
                font-size: 3em;
                height: 70px;
                padding-top: 5px;
            }
        }
    </style>
@endpush

@section('breadcrumb')
    @parent
    <li class="active">Transaksi Penjualan</li>
@endsection

@section('content')
      <div class="row">
        <div class="col-md-12">
          <div class="box">
            <div class="box-body">
                <form class="form-produk">
                    @csrf
                    <div class="form-group row">
                        <label for="harga_type" class="col-lg-1">Tipe Harga</label>
                        <div class="col-lg-5">
                            <select name="harga_type" id="harga_type" class="form-control">
                                <option value="harga_jual">Harga Sales</option>
                                <option value="harga_jual_toko">Harga Toko</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group row">
                    <label for="kode_produk" class="col-lg-1">Kode Produk</label>
                <div class="col-lg-5">
                    <div class="input-group">
                        <input type="hidden" name="id_penjualan" id="id_penjualan" value="{{ $id_penjualan }}">
                        <input type="hidden" name="id_produk" id="id_produk">
                        <input type="text" class="form-control" name="kode_produk" id="kode_produk">
                        <span class="input-group-btn">
                            <button onclick="tampilProduk()" class="btn btn-info btn-flat" type="button"><i class="fa fa-arrow-right"></i></button>
                        </span>
                    </div>
                </div>
            </div>
                </form>

                <table class="table table-stiped table-bordered table-penjualan">
                    <thead>
                        <th width="5%">No</th>
                        <th>Kode</th>
                        <th>Nama</th>
                        <th>Harga</th>
                        <th width="15%">Jumlah</th>
                        <th width="15%">Diskon %</th>
                        <th>Subtotal</th>
                        <th width="15%"><i class="fa fa-cog"></i></th>
                    </thead>
                </table>

                <div class="row">
                    <div class="col-lg-8">
                        <div class="tampil-bayar bg-primary"></div>
                        <div class="tampil-terbilang"></div>
                    </div>
                    <div class="col-lg-4">
                        <form action="{{ route('transaksi.simpan') }}" class="form-penjualan" method="post">
                            @csrf
                            <input type="hidden" name="id_penjualan" value="{{ $id_penjualan }}">
                            <input type="hidden" name="total" id="total">
                            <input type="hidden" name="total_item" id="total_item">
                            <input type="hidden" name="bayar" id="bayar">
                            <input type="hidden" name="id_customer" id="id_customer" value="{{ $customerSelected->id_customer }}" >

                            <div class="form-group row">
                                <label for="kode_customer" class="col-lg-2 control-label">Customer</label>
                                <div class="col-lg-8">
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="kode_customer" value="{{ $customerSelected->nama }}" readonly>
                                        <span class="input-group-btn">
                                            <button onclick="tampilCustomer()" class="btn btn-info btn-flat" type="button"><i class="fa fa-arrow-right"></i></button>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="totalrp" class="col-lg-2 control-label">Total</label>
                                <div class="col-lg-8">
                                    <input type="text" id="totalrp" class="form-control" readonly>
                                </div>
                            </div>
                            
                            <div class="form-group row">
                                <label for="diskon" class="col-lg-2 control-label">Diskon %</label>
                                <div class="col-lg-8">
                                    <input type="number" name="diskon" id="diskon" class="form-control" value="{{ $diskonGlobal ?? 0 }}" >
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="bayar" class="col-lg-2 control-label">Bayar</label>
                                <div class="col-lg-8">
                                    <input type="text" id="bayarrp" class="form-control" readonly>
                                </div>
                            </div>
                           <div class="form-group row">
                                <label for="diterima" class="col-lg-2 control-label">Diterima</label>
                                <div class="col-lg-8">
                                    <div class="input-group">
                                        <input type="text" id="diterima" class="form-control" name="diterima" value="{{ $penjualan->diterima ?? 0 }}">
                                        <span class="input-group-btn">
                                            <button type="button" id="btn-uang-pas" class="btn btn-success btn-flat">Uang Pas</button>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="kembali" class="col-lg-2 control-label">Kembali</label>
                                <div class="col-lg-8">
                                    <input type="text" id="kembali" class="form-control" name="kembali" value="0" readonly>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="box-footer">
                <button type="submit" class="btn btn-primary btn-sm btn-flat pull-right btn-simpan"><i class="fa fa-floppy-o"></i> Simpan Transaksi</button>
            </div>
          </div>
        </div>
      </div>

@includeIf('penjualan_detail.produk')
@includeIf('penjualan_detail.customer')
@endsection

@push('scripts')
    <script>
        let table, table2;

        $(function () {
            table = $('.table-penjualan').DataTable({
            processing: true,
            autoWidth: false,
             ajax: {
                 url: '{{ route('transaksi.data', $id_penjualan) }}',
             },
             columns: [
                {data: 'DT_RowIndex', searchable: false, sortable: false},
                {data: 'kode_produk'},
                {data: 'nama_produk'},
                {data: 'harga_jual'},
                {data: 'jumlah'},
                {data: 'diskon'},
                {data: 'subtotal'},
                {data: 'aksi', searchable: false, sortable: false},
             ],
             dom: 'Brt',
             bSort: false,
             paging: false,
        })
        .on('draw.dt', function () {
            loadForm($('#diskon').val());
            setTimeout(() => {
                $('#diterima').trigger('input');
            }, 300);
        });
        table2 = $('.table-produk').DataTable();

        tableCustomer = $('.table-customer').DataTable({
        autoWidth: false
        });

        $('#harga_type').on('change', function () {
            updateHargaProduk();

            let id_penjualan = $('#id_penjualan').val();
            let harga_type = $(this).val();

            $.post('{{ route('transaksi.update_harga') }}', {
                '_token': $('[name=csrf-token]').attr('content'),
                'id_penjualan': id_penjualan,
                'harga_type': harga_type
            })
            .done(response => {
                // Refresh tabel agar harga berubah
                table.ajax.reload(() => loadForm($('#diskon').val()));
            })
            .fail(errors => {
                alert('Tidak dapat mengupdate harga');
                return;
            });
        });

        $('#kode_produk').on('keypress', function(e) {
        if (e.which == 13) { // Enter key
            e.preventDefault();

            let kode = $('#kode_produk').val();

            if (kode == '') return;

            $.get(`{{ url('/produk/cari') }}/${kode}`)
                .done(response => {
                    if (response.success) {
                        $('#id_produk').val(response.id_produk);
                        tambahProduk(); // langsung tambah
                    } else {
                        alert('Produk tidak ditemukan!');
                    }
                })
                .fail(errors => {
                    alert('Gagal mencari produk!');
                });
        }
        });

        $(document).ready(function() {
        $('#kode_produk').focus();
        });

        $('#modal-produk').on('shown.bs.modal', function() {
            $(this).find('input[type="search"]').focus();
        });


        $(document).on('change', '.quantity', function () {
            let id = $(this).data('id');
            let jumlah = parseInt($(this).val());

            if (jumlah < 1) {
                alert('Jumlah tidak boleh kurang dari 1');
                return;
            }

            if (jumlah > 10000) {
                alert('Jumlah tidak boleh lebih dari 10000');
                $(this).val(10000)
                return;
            }

            $.post(`{{ url('/transaksi') }}/${id}`, {
                '_token': $('[name=csrf-token]').attr('content'),
                '_method': 'put',
                'jumlah': jumlah
                })
                .done(response => {
                table.ajax.reload(() => loadForm($('#diskon').val())); 
                })
                .fail(errors => {
                  if (errors.responseJSON && errors.responseJSON.message) {
                    alert(errors.responseJSON.message);
                } else {
                    alert('Terjadi kesalahan, tidak dapat menyimpan data');
                }
                });
        });

        $(document).on('keydown', '.quantity', function(e) {
            if (e.keyCode === 13 && !e.shiftKey) {
                e.preventDefault();
                $(this).closest('tr').find('.diskon').focus().select();
            }
        });

        $(document).on('keydown', '.diskon', function(e) {
            if ((e.keyCode === 9 || e.keyCode === 13) && !e.shiftKey) {
                e.preventDefault();
                var currentRow = $(this).closest('tr');
                var nextRow = currentRow.next('tr');
                if (nextRow.length) {
                    nextRow.find('.quantity').focus().select();
                } else {
                    $('#kode_produk').focus();
                }
            }
        });

        $(document).on('change', '.diskon', function () {
            let id = $(this).data('id');
            let diskon = parseInt($(this).val());
            let jumlah = $(this).closest('tr').find('.quantity').val();

            if (diskon < 0) {
                alert('Diskon tidak boleh kurang dari ');
                return;
            }

            if (diskon > 100) {
                alert('Diskon tidak boleh lebih dari 100');
                $(this).val(0)
                return;
            }

            $.post(`{{ url('/transaksi') }}/${id}`, {
                '_token': $('[name=csrf-token]').attr('content'),
                '_method': 'put',
                'jumlah': jumlah,
                'diskon': diskon
                })
                .done(response => {
                table.ajax.reload(null, false); 
                })
                .fail(errors => {
                });
        });
    });

        $(document).on('change', '#diskon', function () {
            if ($(this).val() == "") {
                $(this).val(0).select();
            }

            loadForm($(this).val());
        });

        $(document).on('blur', '.diskon', function() {
            let value = $(this).val();
            if (value === '' || isNaN(value)) {
            $(this).val(0).trigger('change'); // ubah jadi 0 & trigger change supaya update
        }
        });

        $(document).on('click', '.pilih-customer', function (e) {
            e.preventDefault();
            let id = $(this).data('id');
            let nama = $(this).data('nama');

            $('#id_customer').val(id);      // simpan ID untuk database
            $('#kode_customer').val(nama);  // tampilkan nama di input
            $('#diterima').focus().select();
            loadForm($('#diskon').val(), $('#diterima').val());
            hideCustomer();                
        });

       
        $('#diterima').on('input', function () {
            if ($(this).val() == "") {
                $(this).val(0).select();
            }

            loadForm($('#diskon').val(), $(this).val());
        }).focus(function () {
            $(this).select();
        });

        $('#btn-uang-pas').on('click', function () {
        let bayar = $('#bayar').val();  // ambil hidden input bayar
        $('#diterima').val(bayar).trigger('input'); // set ke input diterima + panggil event input biar loadForm jalan
        });

        $('.btn-simpan').on('click', function (e) {
            e.preventDefault();

            let customer = $('#id_customer').val();
            let bayar = parseFloat($('#bayar').val()); // Ambil nilai 'Bayar' yang sudah dihitung
            let diterima = parseFloat($('#diterima').val().replace(/[^\d]/g, '')); // Ambil nilai 'Diterima'

            if (customer == "" || customer == null) {
                alert('Tolong pilih customer');
                return;
            }
            
            if (diterima < bayar) {
                alert('Uang yang diterima kurang dari total yang harus dibayar!');
                $('#diterima').focus().select();
                return;
            }

            $('.form-penjualan').submit();
        });
        
        function tampilProduk() {
            updateHargaProduk();
            $('#modal-produk').modal('show');
        }

        function updateHargaProduk() {
        let priceType = $('#harga_type').val();

        $('#modal-produk .table-produk tbody tr').each(function () {
            let row = $(this);
            let hargaSales = row.data('harga-sales');
            let hargaToko = row.data('harga-toko');
            let hargaCell = row.find('.harga-display');

            if (priceType === 'harga_jual_toko') {
                hargaCell.text(hargaToko);
            } else {
                hargaCell.text(hargaSales);
            }
        });
    }

        function hideProduk() {
            $('#modal-produk').modal('hide');
        }

        function pilihProduk(id, kode) {
            $('#id_produk').val(id);
            $('#kode_produk').val(kode);
            hideProduk();
            tambahProduk();
        }

         function tambahProduk() {
            let data = $('.form-produk').serialize() + '&harga_type=' + $('#harga_type').val();
            $.post('{{ route('transaksi.store') }}', data)
                .done(response => {
                    $('#kode_produk').val('').focus();
                    table.ajax.reload(() => loadForm($('#diskon').val()));
                })
                .fail(errors => {
                if (errors.responseJSON && errors.responseJSON.message) {
                    alert(errors.responseJSON.message); // tampilkan pesan dari backend
                } else {
                    alert('Terjadi kesalahan, tidak dapat menyimpan data');
                }
                return;
                });

        }

        function tampilCustomer() {
            $('#modal-customer').modal('show');
            
        }

        function hideCustomer() {
            $('#modal-customer').modal('hide');
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

        function loadForm(diskon = 0, diterima = 0) {
        $('#total').val($('.total').text());
        $('#total_item').val($('.total_item').text());

        $.get(`{{ url('/transaksi/loadform') }}/${diskon}/${$('.total').text()}/${diterima}`)
            .done(response => {
                $('#totalrp').val('Rp. '+ response.totalrp);
                $('#bayarrp').val('Rp. '+ response.bayarrp);
                $('#bayar').val(response.bayar);
                $('.tampil-bayar').text('Bayar: Rp. '+ response.bayarrp);
                $('.tampil-terbilang').text(response.terbilang);

                $('#kembali').val('Rp.'+ response.kembalirp);
                if ($('#diterima').val() != 0) {
                    $('.tampil-bayar').text('Kembali: Rp. '+ response.kembalirp);
                    $('.tampil-terbilang').text(response.kembali_terbilang);
                }
            })
            .fail(errors => {
                alert('Tidak dapat menampilkan data');
                return;
            })
    }

    </script>
@endpush