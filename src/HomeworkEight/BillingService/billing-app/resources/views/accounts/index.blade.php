@extends('layout.app')
@section('content')

    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>Счета</h2>
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
            <th>ID счета</th>
            <th>ID пользователя</th>
            <th>Баланс</th>
        </tr>
        @foreach ($accounts as $account)
            <tr>
                <td>{{ $account->id }}</td>
                <td>{{ $account->user_id }}</td>
                <td>{{ $account->balance }}</td>
            </tr>
        @endforeach
    </table>

@endsection
