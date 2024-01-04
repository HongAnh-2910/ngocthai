@extends('layouts.app')
@section('title', __('warehouse.warehouses'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang( 'warehouse.warehouses' )
        <small>@lang( 'warehouse.manage_your_warehouses' )</small>
    </h1>
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => __( 'warehouse.all_your_warehouses' )])
        @can('warehouse.create')
            @slot('tool')
                <div class="box-tools">
                    <button type="button" class="btn btn-block btn-primary btn-modal"
                        data-href="{{action('WarehouseController@create')}}"
                        data-container=".warehouses_modal">
                        <i class="fa fa-plus"></i> @lang( 'messages.add' )</button>
                </div>
            @endslot
        @endcan
        @can('warehouse.view')
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="warehouses_table">
                    <thead>
                        <tr>
                            <th>@lang( 'warehouse.warehouses' )</th>
                            <th>@lang( 'warehouse.business_location' )</th>
                            <th>@lang( 'warehouse.note' )</th>
                            <th>@lang( 'messages.action' )</th>
                        </tr>
                    </thead>
                </table>
            </div>
        @endcan
    @endcomponent

    <div class="modal fade warehouses_modal" tabindex="-1" role="dialog"
    	aria-labelledby="gridSystemModalLabel">
    </div>

</section>
<!-- /.content -->
@endsection

@section('javascript')
    <script type="text/javascript">
        $(document).ready( function(){
            warehouses_table = $('#warehouses_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '/warehouses',
                },
                aaSorting: [[1, 'desc']],
                columns: [
                    { data: 'name', name: 'name' },
                    { data: 'business_location', name: 'business_location' },
                    { data: 'description', name: 'description' },
                    { data: 'action', name: 'action', orderable: false, searchable: false },
                ],
            });

            $(document).on('submit', 'form#warehouse_add_form', function(e) {
                e.preventDefault();
                $(this)
                    .find('button[type="submit"]')
                    .attr('disabled', true);
                var data = $(this).serialize();

                $.ajax({
                    method: 'POST',
                    url: $(this).attr('action'),
                    dataType: 'json',
                    data: data,
                    success: function(result) {
                        if (result.success == true) {
                            $('div.warehouses_modal').modal('hide');
                            toastr.success(result.msg);
                            warehouses_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            });

            $(document).on('click', 'button.edit_warehouse_button', function() {
                $('div.warehouses_modal').load($(this).data('href'), function() {
                    $(this).modal('show');

                    $('form#warehouse_edit_form').submit(function(e) {
                        e.preventDefault();
                        $(this)
                            .find('button[type="submit"]')
                            .attr('disabled', true);
                        var data = $(this).serialize();

                        $.ajax({
                            method: 'POST',
                            url: $(this).attr('action'),
                            dataType: 'json',
                            data: data,
                            success: function(result) {
                                if (result.success == true) {
                                    $('div.warehouses_modal').modal('hide');
                                    toastr.success(result.msg);
                                    warehouses_table.ajax.reload();
                                } else {
                                    toastr.error(result.msg);
                                }
                            },
                        });
                    });
                });
            });

            $(document).on('click', 'button.delete_warehouse_button', function() {
                swal({
                    title: LANG.sure,
                    text: LANG.confirm_delete_warehouse,
                    icon: 'warning',
                    buttons: true,
                    dangerMode: true,
                }).then(willDelete => {
                    if (willDelete) {
                        var href = $(this).data('href');
                        var data = $(this).serialize();

                        $.ajax({
                            method: 'DELETE',
                            url: href,
                            dataType: 'json',
                            data: data,
                            success: function(result) {
                                if (result.success == true) {
                                    toastr.success(result.msg);
                                    warehouses_table.ajax.reload();
                                } else {
                                    toastr.error(result.msg);
                                }
                            },
                        });
                    }
                });
            });
        });
    </script>
@endsection
