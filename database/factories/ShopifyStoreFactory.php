<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ShopifyStore>
 */
class ShopifyStoreFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'shop_domain' => fake()->unique()->lexify('?????-????').'.myshopify.com',
            'access_token' => fake()->sha256(),
            'shop_name' => fake()->company(),
            'scopes' => 'read_products,read_metafields',
        ];
    }
}
