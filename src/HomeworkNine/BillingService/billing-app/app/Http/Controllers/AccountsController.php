<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Order;

class AccountsController extends Controller
{
    public function index()
    {
        $accounts = Account::all();

        return view('accounts.index', compact('accounts'));
    }
}
