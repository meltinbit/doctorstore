<?php

namespace App\Policies;

use App\Models\ShopifyStore;
use App\Models\User;

class ShopifyStorePolicy
{
    public function view(User $user, ShopifyStore $shopifyStore): bool
    {
        return $user->id === $shopifyStore->user_id;
    }

    public function update(User $user, ShopifyStore $shopifyStore): bool
    {
        return $user->id === $shopifyStore->user_id;
    }

    public function delete(User $user, ShopifyStore $shopifyStore): bool
    {
        return $user->id === $shopifyStore->user_id;
    }
}
