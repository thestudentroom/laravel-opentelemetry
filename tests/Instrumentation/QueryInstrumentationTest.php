<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\SDK\Common\Time\ClockFactory;
use OpenTelemetry\SDK\Trace\ImmutableSpan;

beforeEach(function () {
    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->boolean('admin')->default(false);
    });
});

it('can watch a query', function () {
    DB::table('users')->get();

    flushSpans();

    $span = Arr::last(getRecordedSpans());
    assert($span instanceof ImmutableSpan);

    expect($span)
        ->getName()->toBe('sql SELECT')
        ->getKind()->toBe(SpanKind::KIND_CLIENT)
        ->getAttributes()->toArray()->toMatchArray([
            'db.system' => 'sqlite',
            'db.name' => ':memory:',
            'db.operation' => 'SELECT',
            'db.statement' => 'select * from "users"',
        ])
        ->hasEnded()->toBeTrue()
        ->getEndEpochNanos()->toBeLessThan(ClockFactory::getDefault()->now());

    expect($span->getEndEpochNanos() - $span->getStartEpochNanos())
        ->toBeGreaterThan(0);
});

it('can watch a query with bindings', function () {
    DB::table('users')
        ->where('id', 1)
        ->where('name', 'like', 'John%')
        ->get();

    flushSpans();

    $span = Arr::last(getRecordedSpans());
    assert($span instanceof ImmutableSpan);

    expect($span)
        ->getName()->toBe('sql SELECT')
        ->getKind()->toBe(SpanKind::KIND_CLIENT)
        ->getAttributes()->toArray()->toMatchArray([
            'db.system' => 'sqlite',
            'db.name' => ':memory:',
            'db.statement' => 'select * from "users" where "id" = ? and "name" like ?',
        ]);
});

it('can watch a query with named bindings', function () {
    DB::table('users')->insert([
        'name' => 'John Doe',
        'admin' => true,
    ]);

    DB::statement(<<<'SQL'
    update "users" set "name" = :name where admin = true
    SQL, [
        'name' => 'Admin',
    ]);

    flushSpans();

    $span = Arr::last(getRecordedSpans());
    assert($span instanceof ImmutableSpan);

    expect($span)
        ->getName()->toBe('sql UPDATE')
        ->getKind()->toBe(SpanKind::KIND_CLIENT)
        ->getAttributes()->toArray()->toMatchArray([
            'db.system' => 'sqlite',
            'db.name' => ':memory:',
            'db.statement' => 'update "users" set "name" = :name where admin = true',
        ]);
});
