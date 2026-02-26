<?php

use App\Enums\ScanStatus;
use App\Jobs\RunStoreScanJob;
use App\Models\Scan;
use App\Models\ScanIssue;
use App\Models\ShopifyStore;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

// ─── store() ────────────────────────────────────────────────────────────────

test('guests are redirected from scan store route', function () {
    $store = ShopifyStore::factory()->create();

    $this->post(route('stores.scans.store', $store))
        ->assertRedirect(route('login'));
});

test('user cannot create scan for another users store', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $store = ShopifyStore::factory()->for($owner)->create();

    $this->actingAs($other)
        ->post(route('stores.scans.store', $store))
        ->assertForbidden();
});

test('store creates a pending scan and dispatches job', function () {
    Queue::fake();

    $user = User::factory()->create();
    $store = ShopifyStore::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('stores.scans.store', $store))
        ->assertRedirect();

    $scan = Scan::where('shopify_store_id', $store->id)->first();

    expect($scan)->not->toBeNull();
    expect($scan->status)->toBe(ScanStatus::Pending);

    Queue::assertPushed(RunStoreScanJob::class, fn ($job) => $job->store->id === $store->id && $job->scan->id === $scan->id);
});

// ─── show() ─────────────────────────────────────────────────────────────────

test('guests are redirected from scan show route', function () {
    $store = ShopifyStore::factory()->create();
    $scan = Scan::factory()->for($store)->create();

    $this->get(route('stores.scans.show', [$store, $scan]))
        ->assertRedirect(route('login'));
});

test('user cannot view scan belonging to another users store', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $store = ShopifyStore::factory()->for($owner)->create();
    $scan = Scan::factory()->for($store)->create();

    $this->actingAs($other)
        ->get(route('stores.scans.show', [$store, $scan]))
        ->assertForbidden();
});

test('show returns correct data for a complete scan', function () {
    $user = User::factory()->create();
    $store = ShopifyStore::factory()->for($user)->create();
    $scan = Scan::factory()->for($store)->complete()->create([
        'total_metafields' => 42,
        'total_definitions' => 10,
        'total_issues' => 2,
    ]);

    ScanIssue::factory()->for($scan)->create([
        'namespace' => 'custom',
        'key' => 'color',
        'resource_type' => 'product',
        'issue_type' => 'empty_metafield',
        'occurrences' => 5,
    ]);

    $response = $this->actingAs($user)
        ->get(route('stores.scans.show', [$store, $scan]));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('stores/scans/show')
        ->where('scan.id', $scan->id)
        ->where('scan.status', 'complete')
        ->where('scan.total_metafields', 42)
        ->where('scan.total_definitions', 10)
        ->where('scan.total_issues', 2)
        ->has('issues', 1)
        ->has('issuesByType')
    );
});

test('show returns pending scan without issues', function () {
    $user = User::factory()->create();
    $store = ShopifyStore::factory()->for($user)->create();
    $scan = Scan::factory()->for($store)->pending()->create();

    $this->actingAs($user)
        ->get(route('stores.scans.show', [$store, $scan]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('scan.status', 'pending')
            ->has('issues', 0)
        );
});

test('user can only see their own stores scans', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $storeA = ShopifyStore::factory()->for($userA)->create();
    $storeB = ShopifyStore::factory()->for($userB)->create();
    $scanB = Scan::factory()->for($storeB)->create();

    $this->actingAs($userA)
        ->get(route('stores.scans.show', [$storeB, $scanB]))
        ->assertForbidden();
});
