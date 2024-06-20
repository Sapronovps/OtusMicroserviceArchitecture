<?php

namespace App\Http\Controllers;

use App\Enums\StatusEnum;
use App\Models\Couriers;
use Illuminate\Http\Request;

class CourierController extends Controller
{
    public function index()
    {
        $couriers = Couriers::all();

        return response()->json([
            'status' => 'success',
            'orders' => $couriers
        ]);
    }

    public function store(Request $request)
    {
        if (empty($request->get('name'))) {
            return $this->sendErrorResponse('Не указан обязательный параметр "name"', 400);
        }

        $courier = new Couriers();
        $courier->name = $request->get('name');
        $courier->status_id = StatusEnum::FREE->value;
        $courier->save();

        return response()->json([
            'status' => 'success',
            'courier' => $courier,
        ]);
    }

    private function sendErrorResponse(string $message, int $status = 200)
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
        ], $status);
    }
}
