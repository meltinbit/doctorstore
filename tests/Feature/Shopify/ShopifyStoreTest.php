<?php

use App\Models\ShopifyStore;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected from stores index', function () {
    $this->get(route('stores.index'))
        ->assertRedirect(route('login'));
});

test('authenticated users can visit stores index', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('stores.index'))
        ->assertOk();
});

test('user only sees their own stores', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $ownStore = ShopifyStore::factory()->create(['user_id' => $user->id, 'shop_domain' => 'my-store.myshopify.com']);
    ShopifyStore::factory()->create(['user_id' => $otherUser->id, 'shop_domain' => 'other-store.myshopify.com']);

    $this->actingAs($user)
        ->get(route('stores.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('stores/index')
            ->has('stores', 1)
            ->where('stores.0.shop_domain', $ownStore->shop_domain)
        );
});

test('user can disconnect their own store', function () {
    $user = User::factory()->create();
    $store = ShopifyStore::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->delete(route('stores.destroy', $store))
        ->assertRedirect(route('stores.index'));

    $this->assertDatabaseMissing('shopify_stores', ['id' => $store->id]);
});

test('user cannot disconnect another users store', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $store = ShopifyStore::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($user)
        ->delete(route('stores.destroy', $store))
        ->assertForbidden();

    $this->assertDatabaseHas('shopify_stores', ['id' => $store->id]);
});
