<?php

declare(strict_types=1);

namespace App\Http\Controllers;

class StatusController extends Controller
{
    public function index()
    {
        return response()->json(['status' => 'OK']);
    }
}
