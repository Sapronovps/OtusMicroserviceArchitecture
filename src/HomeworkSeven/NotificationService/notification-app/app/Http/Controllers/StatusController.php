<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\MetricsService;

class StatusController extends Controller
{
    private ?MetricsService $metricsService = null;

    public function index()
    {
        $startTime = microtime(true);
        $this->getMetricsService()->registerRps();
        $this->getMetricsService()->registerLatency(microtime(true) - $startTime);

        return response()->json(['status' => 'OK']);
    }

    public function register500()
    {
        $this->getMetricsService()->register500();

        return response()->json(['status' => 'Registered 500 error']);
    }

    private function getMetricsService(): MetricsService
    {
        return $this->metricsService ??= new MetricsService();
    }
}
