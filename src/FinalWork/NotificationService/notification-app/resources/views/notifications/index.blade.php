@php use App\Enums\StatusEnum; @endphp
@extends('layout.app')
@section('content')

    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>Уведомления</h2>
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
            <th>Email</th>
            <th>Сообщение</th>
            <th>Статус</th>
        </tr>
        @foreach ($notifications as $notification)
            <tr>
                <td>{{ $notification->id }}</td>
                <td>{{ $notification->email }}</td>
                <td>{{ $notification->message }}</td>
                <td>{{ StatusEnum::tryFrom($notification->status_id)->getName() }}</td>
            </tr>
        @endforeach
    </table>

@endsection
