<?php

declare(strict_types=1);

namespace App\Services;

use Prometheus\CollectorRegistry;
use Prometheus\Exception\MetricsRegistrationException;
use Prometheus\Storage\APC;

/**
 * Севрис метрик.
 */
class MetricsService
{
    private ?CollectorRegistry $registry = null;

    private const NAMESPACE = 'otus';

    /**
     * Регистрация метрики RPS.
     *
     * @return void
     * @throws MetricsRegistrationException
     */
    public function registerRps(): void
    {
        $counter = $this->getRegistry()
            ->registerCounter(self::NAMESPACE, 'rps', 'RPS приложения', ['url']);
        $counter->inc([$_SERVER['REQUEST_URI']]);
    }

    /**
     * Регистрация метрики Latency.
     *
     * @param float $durationTime
     * @return void
     * @throws MetricsRegistrationException
     */
    public function registerLatency(float $durationTime): void
    {
        $histogram = $this->getRegistry()
            ->getOrRegisterSummary(
                'otus',
                'latency',
                'Latency (response time)',
                ['url'],
                600,
                [0.5, 0.95, 0.99]
            );
        $histogram->observe(round($durationTime, 3), [$_SERVER['REQUEST_URI']]);
    }

    /**
     * Регистрация метрики 500-х ответов.
     *
     * @return void
     * @throws MetricsRegistrationException
     */
    public function register500(): void
    {
        // Количество 500 ответов
        $counter = $this->getRegistry()
            ->registerCounter('otus', 'errorResponse', 'Количество 500 ответов', ['url']);

        $counter->inc([$_SERVER['REQUEST_URI']]);
    }

    private function getRegistry(): CollectorRegistry
    {
        return $this->registry ??= new CollectorRegistry(new APC());
    }
}
