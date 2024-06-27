<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\MetricsService;
use cebe\openapi\Reader;
use Illuminate\Http\Request;

class UserController extends Controller
{
    private ?MetricsService $metricsService = null;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $startTime = microtime(true);
        $this->getMetricsService()->registerRps();

        $users = User::all();

        $this->getMetricsService()->registerLatency(microtime(true) - $startTime);

        return view('users.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $startTime = microtime(true);
        $this->getMetricsService()->registerRps();
        $this->getMetricsService()->registerLatency(microtime(true) - $startTime);

        return view('users.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $startTime = microtime(true);
        $this->getMetricsService()->registerRps();

        $request->validate([
            'name' => 'required',
            'email' => 'required',
        ]);

        User::create($request->all());

        $this->getMetricsService()->registerLatency(microtime(true) - $startTime);

        return redirect()->route('users.index')->with('success', 'User created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        $startTime = microtime(true);
        $this->getMetricsService()->registerRps();
        $this->getMetricsService()->registerLatency(microtime(true) - $startTime);

        return view('users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        $startTime = microtime(true);
        $this->getMetricsService()->registerRps();
        $this->getMetricsService()->registerLatency(microtime(true) - $startTime);

        return view('users.edit', compact('user'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $startTime = microtime(true);
        $this->getMetricsService()->registerRps();

        $request->validate([
            'name' => 'required',
            'email' => 'required',
        ]);

        $user->update($request->all());

        $this->getMetricsService()->registerLatency(microtime(true) - $startTime);

        return redirect()->route('users.index')->with('success', 'Пользователь успешно обновлен');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $startTime = microtime(true);
        $this->getMetricsService()->registerRps();

        $user->delete();

        $this->getMetricsService()->registerLatency(microtime(true) - $startTime);

        return redirect()->route('users.index')->with('success', 'Пользователь успешно удален');
    }

    private function getMetricsService(): MetricsService
    {
        return $this->metricsService ??= new MetricsService();
    }
}
