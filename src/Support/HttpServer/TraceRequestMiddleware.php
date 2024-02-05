<?php

namespace Keepsuit\LaravelOpenTelemetry\Support\HttpServer;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Keepsuit\LaravelOpenTelemetry\Facades\Tracer;
use Keepsuit\LaravelOpenTelemetry\Instrumentation\HttpServerInstrumentation;
use OpenTelemetry\API\Trace\SpanInterface;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\SemConv\TraceAttributes;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TraceRequestMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        if ($request->is(HttpServerInstrumentation::getExcludedPaths())) {
            return $next($request);
        }

        $span = $this->startTracing($request);
        $scope = $span->activate();

        try {
            $response = $next($request);

            if ($response instanceof Response) {
                $this->recordHttpResponseToSpan($span, $response);
            }

            return $response;
        } catch (\Throwable $exception) {
            Tracer::recordExceptionToSpan($span, $exception);

            throw $exception;
        } finally {
            $scope->detach();
            $span->end();
        }
    }

    protected function startTracing(Request $request): SpanInterface
    {
        $context = Tracer::extractContextFromPropagationHeaders($request->headers->all());

        /** @var non-empty-string $route */
        $route = rescue(fn () => Route::getRoutes()->match($request)->uri(), $request->path(), false);
        $route = str_starts_with($route, '/') ? $route : '/'.$route;

        $span = Tracer::build(name: $route)
            ->setSpanKind(SpanKind::KIND_SERVER)
            ->setParent($context)
            ->startSpan();

        Tracer::setRootSpan($span);

        $span
            ->setAttribute(TraceAttributes::URL_FULL, $request->fullUrl())
            ->setAttribute(TraceAttributes::URL_PATH, $request->path() === '/' ? $request->path() : '/'.$request->path())
            ->setAttribute(TraceAttributes::URL_QUERY, $request->getQueryString())
            ->setAttribute(TraceAttributes::URL_SCHEME, $request->getScheme())
            ->setAttribute(TraceAttributes::HTTP_ROUTE, $route)
            ->setAttribute(TraceAttributes::HTTP_REQUEST_METHOD, $request->method())
            ->setAttribute(TraceAttributes::HTTP_REQUEST_BODY_SIZE, $request->header('Content-Length'))
            ->setAttribute(TraceAttributes::SERVER_ADDRESS, $request->getHttpHost())
            ->setAttribute(TraceAttributes::SERVER_PORT, $request->getPort())
            ->setAttribute(TraceAttributes::USER_AGENT_ORIGINAL, $request->userAgent())
            ->setAttribute(TraceAttributes::NETWORK_PROTOCOL_VERSION, $request->getProtocolVersion())
            ->setAttribute(TraceAttributes::NETWORK_PEER_ADDRESS, $request->ip());

        return $span;
    }

    protected function recordHttpResponseToSpan(SpanInterface $span, Response $response): void
    {
        $span->setAttribute(TraceAttributes::HTTP_RESPONSE_STATUS_CODE, $response->getStatusCode());

        if (($content = $response->getContent()) !== false) {
            $span->setAttribute(TraceAttributes::HTTP_RESPONSE_BODY_SIZE, strlen($content));
        }

        if ($response->isSuccessful()) {
            $span->setStatus(StatusCode::STATUS_OK);
        }

        if ($response->isServerError() || $response->isClientError()) {
            $span->setStatus(StatusCode::STATUS_ERROR);
        }
    }
}
