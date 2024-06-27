@php use App\Enums\StatusEnum; @endphp
@extends('layout.app')
@section('content')

    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>Созданные заказы</h2>
            </div>
        </div>
    </div>

    @if ($message = Session::get('success'))
        <div class="alert alert-success">
            <p>{{ $message }}</p>
        </div>
    @endif

    <table class="table table-bordered">
        <tr>
            <th>ID заказа</th>
            <th>ID пользователя</th>
            <th>Статус</th>
        </tr>
        @foreach ($orders as $order)
            <tr>
                <td>{{ $order->id }}</td>
                <td>{{ $order->user_id }}</td>
                <td>{{ StatusEnum::from($order->status_id)->getName() }}</td>
            </tr>
        @endforeach
    </table>

@endsection
