<?php

namespace App\Http\Controllers;

use App\Models\Orders;

class OrdersController extends Controller
{
    public function index()
    {
        $orders = Orders::all();

        return view('orders.index', compact('orders'));
    }
}
