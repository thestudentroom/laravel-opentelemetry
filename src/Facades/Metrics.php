<?php

namespace Keepsuit\LaravelOpenTelemetry\Facades;

use Illuminate\Support\Facades\Facade;
use OpenTelemetry\API\Metrics\CounterInterface;
use OpenTelemetry\API\Metrics\HistogramInterface;
use OpenTelemetry\API\Metrics\ObservableCounterInterface;
use OpenTelemetry\API\Metrics\ObservableGaugeInterface;
use OpenTelemetry\API\Metrics\ObservableUpDownCounterInterface;
use OpenTelemetry\API\Metrics\UpDownCounterInterface;
use OpenTelemetry\API\Metrics\GaugeInterface;

/**
 * @method static CounterInterface createCounter(string $name, string $unit = '', string $description = '')
 * @method static UpDownCounterInterface createUpDownCounter(string $name, string $unit = '', string $description = '')
 * @method static HistogramInterface createHistogram(string $name, string $unit = '', string $description = '')
 * @method static ObservableCounterInterface createObservableCounter(string $name, string $unit = '', string $description = '')
 * @method static ObservableUpDownCounterInterface createObservableUpDownCounter(string $name, string $unit = '', string $description = '')
 * @method static GaugeInterface createGauge(string $name, string $unit = '', string $description = '')
 * @method static ObservableGaugeInterface createObservableGauge(string $name, string $unit = '', string $description = '')
 *
 * @see \Keepsuit\LaravelOpenTelemetry\Metrics
 */
class Metrics extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Keepsuit\LaravelOpenTelemetry\Metrics::class;
    }
}
