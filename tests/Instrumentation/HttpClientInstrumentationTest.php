<?php

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Server\Server;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Keepsuit\LaravelOpenTelemetry\Facades\Tracer;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;

beforeEach(function () {
    Server::start();
});

afterEach(function () {
    Server::stop();
});

it('injects propagation headers to Http client request', function () {
    Server::enqueue([
        new Response(200, ['Content-Length' => 0]),
    ]);

    $root = Tracer::start('root');
    $scope = $root->activate();

    Http::withTrace()->get(Server::$url);

    $traceId = Tracer::traceId();

    $scope->detach();
    $root->end();

    $spans = getRecordedSpans();

    $httpSpan = Arr::get($spans, count($spans) - 2);

    $request = Server::received()[0];

    expect($request)
        ->hasHeader('traceparent')->toBeTrue()
        ->getHeader('traceparent')->toBe([sprintf('00-%s-%s-01', $traceId, $httpSpan->getSpanId())]);
});

it('create http client span', function () {
    Server::enqueue([
        new Response(200, ['Content-Length' => 0]),
    ]);

    Http::withTrace()->get(Server::$url);

    $spans = getRecordedSpans();

    $httpSpan = Arr::last($spans);

    expect($httpSpan)
        ->getKind()->toBe(SpanKind::KIND_CLIENT)
        ->getName()->toBe('HTTP GET')
        ->getStatus()->getCode()->toBe(StatusCode::STATUS_UNSET)
        ->getAttributes()->toMatchArray([
            'http.method' => 'GET',
            'http.url' => 'http://127.0.0.1/',
            'http.target' => '/',
            'http.request_content_length' => 0,
            'http.response_content_length' => 0,
            'http.status_code' => 200,
        ]);
});

it('set span status to error on 4xx and 5xx status code', function () {
    Server::enqueue([
        new Response(500, ['Content-Length' => 0]),
    ]);

    Http::withTrace()->get(Server::$url);

    $spans = getRecordedSpans();

    $httpSpan = Arr::last($spans);

    expect($httpSpan)
        ->getKind()->toBe(SpanKind::KIND_CLIENT)
        ->getName()->toBe('HTTP GET')
        ->getStatus()->getCode()->toBe(StatusCode::STATUS_ERROR)
        ->getAttributes()->toMatchArray([
            'http.status_code' => 500,
        ]);
});
