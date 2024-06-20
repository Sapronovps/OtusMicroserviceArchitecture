<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\MetricsService;
use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use Prometheus\Storage\APC;

class MetricsController extends Controller
{
    public function index()
    {
        $startTime = microtime(true);
        $service = new MetricsService();
        $service->registerRps();

        $durationTime = microtime(true) - $startTime;
        $service->registerLatency($durationTime);

        $registry = new CollectorRegistry(new APC());
        $renderer = new RenderTextFormat();
        $result = $renderer->render($registry->getMetricFamilySamples());

        return response($result, 200, ['Content-type' => 'text/plain']);
    }
}
