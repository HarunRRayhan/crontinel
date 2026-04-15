<?php

use App\Models\StatusPage;
use App\Models\StatusPageEndpoint;
use App\Models\Team;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// Copy to: app/tests/Feature/StatusPageTest.php

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->team = Team::factory()->create(['user_id' => $this->user->id]);
    $this->user->current_team_id = $this->team->id;
    $this->user->save();
    $this->actingAs($this->user);
});

// --- CRUD ---

test('user can create a status page', function () {
    $this->post('/status-pages', [
        'name' => 'My App',
        'description' => 'Testing',
        'is_public' => true,
    ])->assertRedirect();

    expect(StatusPage::where('team_id', $this->team->id)->count())->toBe(1);
    expect(StatusPage::first()->slug)->toBe('my-app');
});

test('slug is auto-generated from name', function () {
    $this->post('/status-pages', ['name' => 'Hello World']);

    expect(StatusPage::first()->slug)->toBe('hello-world');
});

test('user cannot exceed free tier status page limit', function () {
    StatusPage::factory()->create(['team_id' => $this->team->id]); // 1 already

    $this->post('/status-pages', ['name' => 'Second Page'])
        ->assertRedirect()
        ->assertSessionHas('error');
});

test('user can delete their status page', function () {
    $page = StatusPage::factory()->create(['team_id' => $this->team->id]);

    $this->delete("/status-pages/{$page->id}")->assertRedirect();

    expect(StatusPage::find($page->id))->toBeNull();
});

test('user cannot delete another team status page', function () {
    $otherTeam = Team::factory()->create();
    $page = StatusPage::factory()->create(['team_id' => $otherTeam->id]);

    $this->delete("/status-pages/{$page->id}")->assertStatus(403);
});

// --- Endpoints ---

test('user can add an endpoint to their status page', function () {
    $page = StatusPage::factory()->create(['team_id' => $this->team->id]);

    $this->post("/status-pages/{$page->id}/endpoints", [
        'name' => 'API Health',
        'url' => 'https://api.example.com/health',
        'interval' => 60,
    ])->assertRedirect();

    expect($page->endpoints()->count())->toBe(1);
});

test('endpoint interval is capped at plan limit for free tier', function () {
    $page = StatusPage::factory()->create(['team_id' => $this->team->id]);

    $this->post("/status-pages/{$page->id}/endpoints", [
        'name' => 'API',
        'url' => 'https://api.example.com/health',
        'interval' => 5, // below free minimum of 60
    ]);

    expect($page->endpoints()->first()->interval)->toBe(60);
});

// --- Public page ---

test('public status page is accessible without auth', function () {
    $page = StatusPage::factory()->create([
        'team_id' => $this->team->id,
        'slug' => 'my-app',
        'is_public' => true,
    ]);

    $this->get('/status/my-app')->assertOk()->assertViewIs('status.show');
});

test('private status page returns 404', function () {
    StatusPage::factory()->create([
        'team_id' => $this->team->id,
        'slug' => 'private',
        'is_public' => false,
    ]);

    $this->get('/status/private')->assertNotFound();
});

test('json api returns correct overall status', function () {
    $page = StatusPage::factory()->create([
        'team_id' => $this->team->id,
        'slug' => 'test',
        'is_public' => true,
    ]);

    StatusPageEndpoint::factory()->create([
        'status_page_id' => $page->id,
        'last_status' => 'up',
    ]);

    $this->getJson('/api/status-pages/test')
        ->assertOk()
        ->assertJsonPath('overall_status', 'up');
});

test('json api returns degraded when some endpoints are down', function () {
    $page = StatusPage::factory()->create([
        'team_id' => $this->team->id,
        'slug' => 'test2',
        'is_public' => true,
    ]);

    StatusPageEndpoint::factory()->create(['status_page_id' => $page->id, 'last_status' => 'up']);
    StatusPageEndpoint::factory()->create(['status_page_id' => $page->id, 'last_status' => 'down']);

    $this->getJson('/api/status-pages/test2')
        ->assertOk()
        ->assertJsonPath('overall_status', 'degraded');
});
