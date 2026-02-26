<?php

use App\Models\ShopifyStore;
use App\Models\User;
use Illuminate\Support\Facades\Http;

test('guests are redirected from shopify redirect', function () {
    $this->post(route('shopify.redirect'), ['shop' => 'test.myshopify.com'])
        ->assertRedirect(route('login'));
});

test('invalid domain format returns validation error', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('shopify.redirect'), ['shop' => 'not a valid store!'])
        ->assertSessionHasErrors('shop');
});

test('valid domain stores state in session and redirects to shopify', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('shopify.redirect'), ['shop' => 'my-store.myshopify.com']);

    $response->assertRedirectContains('my-store.myshopify.com/admin/oauth/authorize');
    expect(session('shopify_oauth_state'))->not->toBeNull();
});

test('callback with invalid state returns 403', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    session()->put('shopify_oauth_state', 'correct-state');

    $this->get(route('shopify.callback', [
        'shop' => 'my-store.myshopify.com',
        'code' => 'code123',
        'state' => 'wrong-state',
        'hmac' => 'anything',
    ]))->assertForbidden();
});

test('callback with invalid hmac returns 403', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $state = 'valid-state-abc';
    session()->put('shopify_oauth_state', $state);

    $this->get(route('shopify.callback', [
        'shop' => 'my-store.myshopify.com',
        'code' => 'code123',
        'state' => $state,
        'hmac' => 'invalid-hmac',
    ]))->assertForbidden();
});

test('successful callback saves store and redirects to stores index', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $shop = 'my-store.myshopify.com';
    $state = 'valid-state-xyz';
    $code = 'auth-code-123';
    $accessToken = 'shpat_abc123';

    session()->put('shopify_oauth_state', $state);

    $params = ['code' => $code, 'shop' => $shop, 'state' => $state];
    ksort($params);
    $message = collect($params)->map(fn ($v, $k) => "{$k}={$v}")->implode('&');
    $hmac = hash_hmac('sha256', $message, config('doctorstore.client_secret', 'test-secret'));

    Http::fake([
        "https://{$shop}/admin/oauth/access_token" => Http::response([
            'access_token' => $accessToken,
            'scope' => 'read_products,read_metafields',
        ]),
        "https://{$shop}/admin/api/*/shop.json" => Http::response([
            'shop' => ['name' => 'My Test Store'],
        ]),
    ]);

    $response = $this->get(route('shopify.callback', [
        'shop' => $shop,
        'code' => $code,
        'state' => $state,
        'hmac' => $hmac,
    ]));

    $response->assertRedirect(route('stores.index'));
    $this->assertDatabaseHas('shopify_stores', [
        'user_id' => $user->id,
        'shop_domain' => $shop,
        'shop_name' => 'My Test Store',
    ]);
});

test('reconnecting an existing store updates it without creating duplicates', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $shop = 'existing-store.myshopify.com';

    ShopifyStore::factory()->create([
        'user_id' => $user->id,
        'shop_domain' => $shop,
        'access_token' => 'old-token',
    ]);

    $state = 'fresh-state-789';
    $code = 'new-code';
    $newToken = 'shpat_newtoken';

    session()->put('shopify_oauth_state', $state);

    $params = ['code' => $code, 'shop' => $shop, 'state' => $state];
    ksort($params);
    $message = collect($params)->map(fn ($v, $k) => "{$k}={$v}")->implode('&');
    $hmac = hash_hmac('sha256', $message, config('doctorstore.client_secret', 'test-secret'));

    Http::fake([
        "https://{$shop}/admin/oauth/access_token" => Http::response([
            'access_token' => $newToken,
            'scope' => 'read_products,read_metafields',
        ]),
        "https://{$shop}/admin/api/*/shop.json" => Http::response([
            'shop' => ['name' => 'Existing Store'],
        ]),
    ]);

    $this->get(route('shopify.callback', [
        'shop' => $shop,
        'code' => $code,
        'state' => $state,
        'hmac' => $hmac,
    ]))->assertRedirect(route('stores.index'));

    expect(ShopifyStore::where('user_id', $user->id)->where('shop_domain', $shop)->count())->toBe(1);
});
