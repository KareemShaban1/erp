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

    <div class="table-responsive">
        <table class="table table-bordered table-striped" id="suggestionProducts_table">
            <thead>
                <tr>
                    <th>@lang('lang_v1.id')</th>
                    <th>@lang('lang_v1.suggest_name')</th>
                    <th>@lang('lang_v1.client')</th>
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


    //Brand table
    var suggestionProducts_table = $('#suggestionProducts_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ action("ApplicationDashboard\suggestionProductController@index") }}',
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
            { data: 'client_name', name: 'client_name' },
            { data: 'created_at', name: 'created_at',
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


    


   
  
   

</script>
@endsection