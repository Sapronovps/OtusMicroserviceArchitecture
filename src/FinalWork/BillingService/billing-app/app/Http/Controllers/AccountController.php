<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\User;
use Illuminate\Http\Request;
use Throwable;

class AccountController extends Controller
{
    public function index()
    {
        $accounts = Account::all();

        return response()->json([
            'status' => 'success',
            'accounts' => $accounts
        ]);
    }

    /**
     * Пополнение счета.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function replenishment(Request $request)
    {
        try {
            $request->validate([
                'user_id' => 'required|integer',
                'sum' => 'required:integer'
            ]);
        } catch (Throwable $ex) {
            return $this->sendErrorResponse($ex->getMessage(), 400);
        }

        $userId = (int)$request->get('user_id');
        $sum = (int)$request->get('sum');

        if ($sum <= 0) {
           return $this->sendErrorResponse('Сумма пополнения должна быть больше 0', 400);
        }

        $user = User::find($userId);

        if (!$user) {
            return $this->sendErrorResponse('Пользователь с ID ' . $userId . ' не найден', 400);
        }

        /** @var ?Account $account */
        $account = Account::where('user_id', $userId)->first();

        if (!$account) {
            $account = new Account();
            $account->user_id = $userId;
            $account->balance = 0;
        }

        $account->balance += $sum;
        $account->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Баланс успешно пополнен и составляет - ' . $account->balance . ' руб.',
        ]);
    }

    public function payment(Request $request)
    {
        try {
            $request->validate([
                'user_id' => 'required|integer',
                'sum' => 'required:integer'
            ]);
        } catch (Throwable $ex) {
            return $this->sendErrorResponse($ex->getMessage(), 400);
        }

        $userId = (int)$request->get('user_id');
        $sum = (int)$request->get('sum');

        if ($sum <= 0) {
            return $this->sendErrorResponse('Сумма пополнения должна быть больше 0', 400);
        }

        $user = User::find($userId);

        if (!$user) {
            return $this->sendErrorResponse('Пользователь с ID ' . $userId . ' не найден', 400);
        }

        /** @var ?Account $account */
        $account = Account::where('user_id', $userId)->first();

        if (!$account) {
            return $this->sendErrorResponse('Для пользователя с ID ' . $userId . ' не создан счет', 400);
        }

        if ($account->balance < $sum) {
            return $this->sendErrorResponse('На счете недостаточно средств', 400);
        }

        $account->balance -= $sum;
        $account->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Заказ успешно оплачен',
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
