<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    public function index()
    {
        $users = User::all();

        return response()->json([
           'status' => 'success',
           'users' => $users
        ]);
    }

    public function login(Request $request)
    {
        $request->validate([
           'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('email', 'password');

        $token = Auth::attempt($credentials);

        if (!$token) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 401);
        }

        $user = Auth::user();

        return response()->json([
            'status' => 'success',
            'user' => $user,
            'authorisation' => [
                'token' => $token,
                'type' => 'bearer',
            ]
        ]);
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
        ]);

        $billingAppUrl = getenv('BILLING_APP_URL');

        // Создадим пользователя в Billing сервисе
        $response = Http::post($billingAppUrl . '/api/register', [
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password
        ]);

        if ($response->status() !== 200) {
            return response()->json([
                'status' => 'error',
                'message' => 'Не удалось создать пользователя. Попробуйте позже.',
            ]);
        }

        $shopAppUrl = getenv('SHOP_APP_URL');

        // Создадим пользователя в Shop сервисе
        $response = Http::post($shopAppUrl . '/api/register', [
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password
        ]);

        if ($response->status() !== 200) {
            return response()->json([
                'status' => 'error',
                'message' => 'Не удалось создать пользователя. Попробуйте позже.',
            ]);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = Auth::login($user);

        return response()->json([
            'status' => 'success',
            'message' => 'User created successfully',
            'user' => $user,
            'authorisation' => [
                'token' => $token,
                'type' => 'bearer',
            ]
        ]);
    }

    public function logout()
    {
        Auth::logout();

        return response()->json([
            'status' => 'success',
            'message' => 'Successfully logged out',
        ]);
    }

    public function refresh()
    {
        return response()->json([
            'status' => 'success',
            'user' => Auth::user(),
            'authorisation' => [
                'token' => Auth::refresh(),
                'type' => 'bearer',
            ]
        ]);
    }

    public function show($id)
    {
        if ((int)Auth::id() !== (int)$id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Запрещено смотреть чужой профиль',
            ]);
        }

        $user = User::find($id);

        return response()->json([
            'status' => 'success',
            'user' => $user
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:6',
        ]);

        if ((int)Auth::id() !== (int)$id) {
            return response()->json([
               'status' => 'error',
               'message' => 'Запрещено редактировать чужой профиль',
            ]);
        }

        $user = User::find($id);
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'User updated successfully',
            'user' => $user
        ]);
    }
}
