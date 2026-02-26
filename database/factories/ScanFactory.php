<?php

namespace Database\Factories;

use DoctorStore\Core\Enums\ScanStatus;
use App\Models\ShopifyStore;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Scan>
 */
class ScanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'shopify_store_id' => ShopifyStore::factory(),
            'status' => ScanStatus::Pending,
            'total_metafields' => 0,
            'total_definitions' => 0,
            'total_issues' => 0,
            'error_message' => null,
            'scanned_at' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => ScanStatus::Pending]);
    }

    public function running(): static
    {
        return $this->state(['status' => ScanStatus::Running]);
    }

    public function complete(): static
    {
        return $this->state([
            'status' => ScanStatus::Complete,
            'total_metafields' => fake()->numberBetween(10, 500),
            'total_definitions' => fake()->numberBetween(5, 100),
            'total_issues' => fake()->numberBetween(0, 50),
            'scanned_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state([
            'status' => ScanStatus::Failed,
            'error_message' => fake()->sentence(),
        ]);
    }
}
