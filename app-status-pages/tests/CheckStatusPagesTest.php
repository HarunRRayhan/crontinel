<?php

use App\Models\StatusPage;
use App\Models\StatusPageEndpoint;
use App\Models\Team;
use Illuminate\Support\Facades\Http;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// Copy to: app/tests/Feature/CheckStatusPagesTest.php

test('command marks endpoint as up on 200 response', function () {
    Http::fake(['*' => Http::response('OK', 200)]);

    $endpoint = StatusPageEndpoint::factory()->create(['last_status' => 'unknown']);

    $this->artisan('status-pages:check')->assertSuccessful();

    expect($endpoint->fresh()->last_status)->toBe('up');
});

test('command marks endpoint as down on non-200 response', function () {
    Http::fake(['*' => Http::response('Error', 500)]);

    $endpoint = StatusPageEndpoint::factory()->create(['last_status' => 'unknown']);

    $this->artisan('status-pages:check')->assertSuccessful();

    expect($endpoint->fresh()->last_status)->toBe('down');
    expect($endpoint->fresh()->last_error)->toContain('500');
});

test('command marks endpoint as down on connection failure', function () {
    Http::fake(['*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('Connection refused')]);

    $endpoint = StatusPageEndpoint::factory()->create(['last_status' => 'unknown']);

    $this->artisan('status-pages:check')->assertSuccessful();

    expect($endpoint->fresh()->last_status)->toBe('down');
});

test('command skips endpoints not yet due', function () {
    Http::fake();

    $endpoint = StatusPageEndpoint::factory()->create([
        'last_check_at' => now()->subMinutes(2),
        'interval' => 5, // not due yet
        'last_status' => 'up',
    ]);

    $this->artisan('status-pages:check')->assertSuccessful();

    Http::assertNothingSent();
    expect($endpoint->fresh()->last_check_at->timestamp)->toBeLessThan(now()->subMinutes(1)->timestamp);
});

test('command records response time', function () {
    Http::fake(['*' => Http::response('OK', 200)]);

    $endpoint = StatusPageEndpoint::factory()->create();

    $this->artisan('status-pages:check');

    expect($endpoint->fresh()->last_response_time_ms)->toBeGreaterThanOrEqual(0);
});

test('command checks specific endpoint when --endpoint flag given', function () {
    Http::fake(['*' => Http::response('OK', 200)]);

    $ep1 = StatusPageEndpoint::factory()->create();
    $ep2 = StatusPageEndpoint::factory()->create();

    $this->artisan("status-pages:check --endpoint={$ep1->id}");

    Http::assertSentCount(1);
});

// TODO: test HEAD→GET fallback when server returns 405 on HEAD
