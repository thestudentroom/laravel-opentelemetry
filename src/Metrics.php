<?php

namespace Keepsuit\LaravelOpenTelemetry;

use OpenTelemetry\API\Metrics\MeterInterface;
use OpenTelemetry\API\Metrics\ObservableCounterInterface;
use OpenTelemetry\API\Metrics\ObservableGaugeInterface;
use OpenTelemetry\API\Metrics\GaugeInterface;
use OpenTelemetry\API\Metrics\ObservableUpDownCounterInterface;
use OpenTelemetry\API\Metrics\UpDownCounterInterface;
use OpenTelemetry\API\Metrics\CounterInterface;
use OpenTelemetry\API\Metrics\HistogramInterface;

class Metrics
{
    public function __construct(
        protected MeterInterface $meter
    ) {}

    public function createCounter(string $name, string $unit = '', string $description = ''): CounterInterface
    {
        return $this->meter->createCounter($name, $unit, $description);
    }

    public function createObservableCounter(string $name, string $unit = '', string $description = ''): ObservableCounterInterface
    {
        return $this->meter->createObservableCounter($name, $unit, $description);
    }

    public function createUpDownCounter(string $name, string $unit = '', string $description = ''): UpDownCounterInterface
    {
        return $this->meter->createUpDownCounter($name, $unit, $description);
    }

    public function createObservableUpDownCounter(string $name, string $unit = '', string $description = ''): ObservableUpDownCounterInterface
    {
        return $this->meter->createObservableUpDownCounter($name, $unit, $description);
    }

    public function createGauge(string $name, string $unit = '', string $description = ''): GaugeInterface
    {
        return $this->meter->createGauge($name, $unit, $description);
    }

    public function createObservableGauge(string $name, string $unit = '', string $description = ''): ObservableGaugeInterface
    {
        return $this->meter->createObservableGauge($name, $unit, $description);
    }

    public function createHistogram(string $name, string $unit = '', string $description = ''): HistogramInterface
    {
        return $this->meter->createHistogram($name, $unit, $description);
    }
}
