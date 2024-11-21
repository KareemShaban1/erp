@extends('layouts.app')
@section('title', 'Order Reports')

@section('content')

<section class="content-header">
    <h1>Order Reports</h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary'])


    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Client Name</th>
                <th>Total Order Amount</th>
                <th>Total Canceled Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($orderStats as $stat)
                <tr>
                    <td>{{ $stat->client->name ?? 'Unknown Client' }}</td> <!-- Display client name -->
                    <td>{{ number_format($stat->total_amount, 2) }}</td>
                    <td>{{ number_format($stat->canceled_amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @endcomponent
</section>

@endsection
