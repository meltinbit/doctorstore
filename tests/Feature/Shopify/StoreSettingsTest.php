<?php

use App\Models\ShopifyStore;
use App\Models\User;

// ─── show() ──────────────────────────────────────────────────────────────────

test('guest cannot access store settings', function () {
    $store = ShopifyStore::factory()->create();

    $this->get(route('stores.settings.show', $store))
        ->assertRedirect(route('login'));
});

test('other user cannot view store settings', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $store = ShopifyStore::factory()->for($owner)->create();

    $this->actingAs($other)
        ->get(route('stores.settings.show', $store))
        ->assertForbidden();
});

test('owner can view store settings page', function () {
    $user = User::factory()->create();
    $store = ShopifyStore::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('stores.settings.show', $store))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('stores/settings')
            ->where('store.id', $store->id)
        );
});

// ─── update() ────────────────────────────────────────────────────────────────

test('guest cannot update store settings', function () {
    $store = ShopifyStore::factory()->create();

    $this->put(route('stores.settings.update', $store), [])
        ->assertRedirect(route('login'));
});

test('other user cannot update store settings', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $store = ShopifyStore::factory()->for($owner)->create();

    $this->actingAs($other)
        ->put(route('stores.settings.update', $store), [
            'auto_scan_enabled' => true,
            'auto_scan_schedule' => 'daily',
            'email_summary_enabled' => false,
        ])
        ->assertForbidden();
});

test('owner can update settings with daily schedule', function () {
    $user = User::factory()->create();
    $store = ShopifyStore::factory()->for($user)->create();

    $this->actingAs($user)
        ->put(route('stores.settings.update', $store), [
            'auto_scan_enabled' => true,
            'auto_scan_schedule' => 'daily',
            'email_summary_enabled' => false,
        ])
        ->assertRedirect();

    expect($store->fresh())
        ->auto_scan_enabled->toBeTrue()
        ->auto_scan_schedule->toBe('daily');
});

test('owner can update settings with weekly schedule', function () {
    $user = User::factory()->create();
    $store = ShopifyStore::factory()->for($user)->create();

    $this->actingAs($user)
        ->put(route('stores.settings.update', $store), [
            'auto_scan_enabled' => true,
            'auto_scan_schedule' => 'weekly',
            'email_summary_enabled' => false,
        ])
        ->assertRedirect();

    expect($store->fresh()->auto_scan_schedule)->toBe('weekly');
});

test('owner can update settings with valid custom cron', function () {
    $user = User::factory()->create();
    $store = ShopifyStore::factory()->for($user)->create();

    $this->actingAs($user)
        ->put(route('stores.settings.update', $store), [
            'auto_scan_enabled' => true,
            'auto_scan_schedule' => '0 12 * * 1',
            'email_summary_enabled' => false,
        ])
        ->assertRedirect();

    expect($store->fresh()->auto_scan_schedule)->toBe('0 12 * * 1');
});

test('rejects invalid cron expression', function () {
    $user = User::factory()->create();
    $store = ShopifyStore::factory()->for($user)->create();

    $this->actingAs($user)
        ->put(route('stores.settings.update', $store), [
            'auto_scan_enabled' => true,
            'auto_scan_schedule' => 'not-a-cron',
            'email_summary_enabled' => false,
        ])
        ->assertInvalid('auto_scan_schedule');
});

test('rejects email_summary_enabled true without email address', function () {
    $user = User::factory()->create();
    $store = ShopifyStore::factory()->for($user)->create();

    $this->actingAs($user)
        ->put(route('stores.settings.update', $store), [
            'auto_scan_enabled' => false,
            'email_summary_enabled' => true,
            'email_summary_address' => null,
        ])
        ->assertInvalid('email_summary_address');
});

test('owner can enable email summary with valid address', function () {
    $user = User::factory()->create();
    $store = ShopifyStore::factory()->for($user)->create();

    $this->actingAs($user)
        ->put(route('stores.settings.update', $store), [
            'auto_scan_enabled' => false,
            'email_summary_enabled' => true,
            'email_summary_address' => 'test@example.com',
        ])
        ->assertRedirect();

    expect($store->fresh())
        ->email_summary_enabled->toBeTrue()
        ->email_summary_address->toBe('test@example.com');
});
