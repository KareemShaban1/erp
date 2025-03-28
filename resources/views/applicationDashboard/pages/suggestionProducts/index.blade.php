@extends('layouts.app')
@section('title', 'suggestion Products')

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('lang_v1.suggestion_products')

    </h1>
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary'])

    @slot('tool')
        <div class="box-tools">
        </div>
        @component('components.filters', ['title' => __('report.filters')])

            @include('applicationDashboard.pages.suggestionProducts.filters')

        @endcomponent

        @endslot

    <div class="table-responsive">
        <table class="table table-bordered table-striped" id="suggestionProducts_table">
            <thead>
                <tr>
                    <th>@lang('lang_v1.id')</th>
                    <th>@lang('lang_v1.suggest_name')</th>
                    <th>@lang('lang_v1.client')</th>
                    <th>@lang('lang_v1.Client_Phone')</th>
                    <th>@lang('lang_v1.Client_Location')</th>
                    <th>@lang('lang_v1.created_at')</th>
                </tr>
            </thead>
        </table>
    </div>
    @endcomponent



</section>
<!-- /.content -->

@stop
@section('javascript')
<script>
    $(document).ready(function () {

        $('#filter_date').click(function () {
            suggestionProducts_table.ajax.reload(); // Reload DataTable with the new date filters
        });

        $('#clear_date').click(function () {
            $('#start_date').val('');
            $('#end_date').val('');

            suggestionProducts_table.ajax.reload();
        });

    //suggestionProducts table
    var suggestionProducts_table = $('#suggestionProducts_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
                url: '{{ action("ApplicationDashboard\SuggestionProductController@index") }}',
                data: function (d) {
                    d.start_date = $('#start_date').val();
                    d.end_date = $('#end_date').val();
                }
            },
        columnDefs: [
            {
                targets: 2,
                // orderable: false,
                // searchable: false,
            },
        ],
        columns: [
            { data: 'id', name: 'id' },
            { data: 'name', name: 'name' },
            { data: 'client_name', name: 'client_name' },
            { data: 'client_phone', name: 'client_phone' },
            { data: 'client_location', name: 'client_location' },
            {
                data: 'created_at', name: 'created_at',
                render: function (data) {
                    // Format the date using JavaScript
                    if (data) {
                        const date = new Date(data);
                        return date.toLocaleString(); // Adjust format as needed
                    }
                    return '';
                }
            },
        ]
    });


    });



</script>
@endsection