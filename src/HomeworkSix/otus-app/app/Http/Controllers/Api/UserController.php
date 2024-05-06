<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\MetricsService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    private ?MetricsService $metricsService = null;

    public function authorize($ability, $arguments = [])
    {
        return false;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $startTime = microtime(true);
        $this->getMetricsService()->registerRps();

        $users = User::all();

        $this->getMetricsService()->registerLatency(microtime(true) - $startTime);

        return UserResource::collection($users);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
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

        $users = User::create($request->all());

        $this->getMetricsService()->registerLatency(microtime(true) - $startTime);

        return new UserResource($users);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $startTime = microtime(true);
        $this->getMetricsService()->registerRps();

        $user->update($request->all());

        $this->getMetricsService()->registerLatency(microtime(true) - $startTime);

        return new UserResource($user);
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

        return response()->json();
    }

    private function getMetricsService(): MetricsService
    {
        return $this->metricsService ??= new MetricsService();
    }
}
