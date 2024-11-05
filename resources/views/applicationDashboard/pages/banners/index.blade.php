@extends('layouts.app')
@section('title', 'Banner')

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('lang_v1.banners')
        <small>@lang('lang_v1.manage_your_banners')</small>
    </h1>
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
    @can('lang_v1.create')
        @slot('tool')
        <div class="box-tools">
            <button type="button" class="btn btn-block btn-primary btn-modal"
                data-href="{{action('ApplicationDashboard\BannerController@create')}}" data-container=".banners_modal">
                <i class="fa fa-plus"></i> @lang('messages.add')</button>
        </div>
        @endslot
    @endcan
    @can('lang_v1.view')
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="banners_table">
                <thead>
                    <tr>
                        <th>@lang('lang_v1.id')</th>
                        <th>@lang('lang_v1.name')</th>
                        <th>@lang('lang_v1.image')</th>
                        <th>@lang('lang_v1.active')</th>
                        <th>@lang('lang_v1.actions')</th>
                    </tr>
                </thead>
            </table>
        </div>
    @endcan
    @endcomponent

    <div class="modal fade banners_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>

</section>
<!-- /.content -->

@stop
@section('javascript')
<script>
    //Brand table
    var banners_table = $('#banners_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ action("ApplicationDashboard\BannerController@index") }}', // Laravel action helper
        columnDefs: [
            {
                targets: 2,
                orderable: false,
                searchable: false,
            },
        ],
        columns: [
            { data: 'id', name: 'id' },
            { data: 'name', name: 'name' },
            { data: 'image', name: 'image' },
            { data: 'active', name: 'active' },
            { data: 'action', name: 'action', orderable: false, searchable: false },

        ]
    });

    $(document).on('submit', 'form#banner_add_form', function (e) {
        e.preventDefault();
        var form = $(this)[0];
        var formData = new FormData(form);

        $.ajax({
            method: 'POST',
            url: $(form).attr('action'),
            data: formData,
            processData: false,  // Required for FormData
            contentType: false,  // Required for FormData
            dataType: 'json',
            beforeSend: function (xhr) {
                __disable_submit_button($(form).find('button[type="submit"]'));
            },
            success: function (result) {
                console.log(result)
                if (result.success == true) {
                    $('div.banners_modal').modal('hide');
                    toastr.success(result.msg);
                    banners_table.ajax.reload();
                } else {
                    console.log(result)
                    toastr.error(result.msg);
                }
            },
            error: function (xhr) {
                console.log(xhr.responseText);

                let response = JSON.parse(xhr.responseText);
                if (response.errors) {
                    // Collect all error messages in an array
                    let errorMessages = Object.values(response.errors).flat();

                    // Show each error message using toastr
                    errorMessages.forEach(message => {
                        toastr.error(message);
                    });
                } else {
                    toastr.error(response.message || 'An error occurred');
                }
            }

        });
    });


    $(document).on('click', 'button.edit_banner_button', function () {
        var href = $(this).data('href');
        $('div.banners_modal').load(href, function () {
            $(this).modal('show');

            $('form#banner_edit_form').submit(function (e) {
                e.preventDefault();
                var form = $(this);
                // var data = form.serialize();
                let formData = new FormData(this); // Create a FormData object

                $.ajax({
                    method: 'POST',
                    url: form.attr('action'),
                    data: formData,
                    processData: false,  // Required for FormData
                    contentType: false,  // Required for FormData
                    beforeSend: function (xhr) {
                        __disable_submit_button(form.find('button[type="submit"]'));
                    },
                    success: function (result) {
                        if (result.success) {
                            $('div.banners_modal').modal('hide');
                            toastr.success(result.msg);
                            banners_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                    error: function (xhr) {
                        toastr.error("test");
                    }
                });
            });
        });
    });

    $(document).on('click', 'button.delete_banner_button', function () {
        var href = $(this).data('href');

        swal({
            title: LANG.sure,
            text: LANG.confirm_delete_banner,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then((willDelete) => {
            if (willDelete) {
                $.ajax({
                    method: 'DELETE',
                    url: href,
                    dataType: 'json',
                    success: function (result) {
                        if (result.success) {
                            toastr.success(result.msg);
                            banners_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                    error: function (xhr) {
                        toastr.error(xhr.responseText || 'An error occurred');
                    }
                });
            }
        });
    });

</script>
@endsection