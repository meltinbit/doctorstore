<?php

namespace Database\Factories;

use DoctorStore\Core\Enums\IssueType;
use DoctorStore\Core\Enums\ResourceType;
use App\Models\Scan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ScanIssue>
 */
class ScanIssueFactory extends Factory
{
    public function definition(): array
    {
        return [
            'scan_id' => Scan::factory(),
            'namespace' => fake()->randomElement(['custom', 'global', 'shopify', 'my_app']),
            'key' => fake()->slug(2),
            'resource_type' => fake()->randomElement(ResourceType::cases())->value,
            'issue_type' => fake()->randomElement(IssueType::cases())->value,
            'occurrences' => fake()->numberBetween(1, 100),
            'details' => null,
        ];
    }
}
