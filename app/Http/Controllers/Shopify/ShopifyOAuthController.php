<?php

namespace App\Http\Controllers\Shopify;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shopify\ConnectShopifyStoreRequest;
use App\Models\ShopifyStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class ShopifyOAuthController extends Controller
{
    public function redirect(ConnectShopifyStoreRequest $request): Response
    {
        $shop = $request->validated('shop');

        $state = Str::random(40);
        $request->session()->put('shopify_oauth_state', $state);

        $query = http_build_query([
            'client_id' => config('doctorstore.client_id'),
            'scope' => config('doctorstore.scopes'),
            'redirect_uri' => config('doctorstore.redirect_uri'),
            'state' => $state,
        ]);

        return Inertia::location("https://{$shop}/admin/oauth/authorize?{$query}");
    }

    public function callback(Request $request): RedirectResponse
    {
        $state = $request->session()->pull('shopify_oauth_state');

        if (! $state || $state !== $request->query('state')) {
            abort(403, 'Invalid OAuth state.');
        }

        if (! $this->isValidHmac($request->except('hmac'), $request->query('hmac'))) {
            abort(403, 'Invalid HMAC signature.');
        }

        $shop = $request->query('shop');
        $code = $request->query('code');

        $tokenResponse = Http::post("https://{$shop}/admin/oauth/access_token", [
            'client_id' => config('doctorstore.client_id'),
            'client_secret' => config('doctorstore.client_secret'),
            'code' => $code,
        ]);

        $accessToken = $tokenResponse->json('access_token');
        $scopes = $tokenResponse->json('scope', config('doctorstore.scopes'));

        $shopResponse = Http::withHeaders([
            'X-Shopify-Access-Token' => $accessToken,
        ])->get("https://{$shop}/admin/api/".config('doctorstore.api_version').'/shop.json');

        $shopName = $shopResponse->json('shop.name');

        ShopifyStore::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'shop_domain' => $shop,
            ],
            [
                'access_token' => $accessToken,
                'shop_name' => $shopName,
                'scopes' => $scopes,
            ]
        );

        return redirect()->route('stores.index')->with('success', "Successfully connected {$shopName}.");
    }

    private function isValidHmac(array $params, string $hmac): bool
    {
        ksort($params);
        $message = collect($params)->map(fn ($v, $k) => "{$k}={$v}")->implode('&');

        return hash_equals(hash_hmac('sha256', $message, config('doctorstore.client_secret')), $hmac);
    }
}
