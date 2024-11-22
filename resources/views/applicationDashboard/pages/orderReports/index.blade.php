@extends('layouts.app')
@section('title', 'Order Reports')

@section('content')

<section class="content-header">
    <h1>{{__('lang_v1.client_orders_reports')}}</h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary'])


    <table class="table table-bordered">
        <thead>
            <tr>
                <th>{{__('lang_v1.Client_Name')}}</th>
                <th>{{__('lang_v1.Total_Order_Amount')}} </th>
                <th>{{__('lang_v1.Total_Cancelled_Amount')}} </th>
            </tr>
        </thead>
        <tbody>
            @foreach ($orderStats as $stat)
                <tr>

                    <td>
                        <a href="{{ route('client.orders', $stat->client->id) }}">
                            {{ $stat->client->contact->name ?? 'Unknown Client' }}
                        </a>
                    </td> <!-- Display client name -->

                    <td>{{ number_format($stat->total_amount, 2) }}</td>
                    <td>{{ number_format($stat->canceled_amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @endcomponent
</section>

@endsection