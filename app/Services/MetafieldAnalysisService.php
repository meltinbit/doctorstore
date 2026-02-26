<?php

namespace App\Services;

use App\Enums\IssueType;
use App\Enums\ResourceType;

class MetafieldAnalysisService
{
    const LONG_TEXT_THRESHOLD = 500;
    const SEO_DUPLICATE_THRESHOLD = 10;

    /**
     * Penalties per issue type (deducted from 100).
     * Structural issues cost more; cosmetic ones less.
     */
    const SCORE_PENALTIES = [
        IssueType::DuplicateNamespace->value        => 10,
        IssueType::ValueWithoutDefinition->value    => 8,
        IssueType::UnusedMetafield->value           => 5,
        IssueType::DefinitionWithoutValues->value   => 5,
        IssueType::SeoDuplicate->value              => 7,
        IssueType::EmptyMetafield->value            => 1,  // per 10 occurrences
        IssueType::LongTextValue->value             => 1,  // per 10 occurrences
        IssueType::ValidationMissing->value         => 3,
    ];

    /**
     * Issue types where penalty is per-10-occurrences rather than per-issue.
     */
    const OCCURRENCE_BASED_TYPES = [
        IssueType::EmptyMetafield->value,
        IssueType::LongTextValue->value,
    ];

    /**
     * @param  array<int, array{namespace: string, key: string, resource_type: string, issue_type: string, occurrences: int, details: array<mixed>|null}>  $issues
     */
    public function calculateScore(array $issues): int
    {
        $penalty = 0;

        foreach ($issues as $issue) {
            $type = $issue['issue_type'];
            $pointsPerUnit = self::SCORE_PENALTIES[$type] ?? 2;

            if (in_array($type, self::OCCURRENCE_BASED_TYPES)) {
                $penalty += (int) ceil($issue['occurrences'] / 10) * $pointsPerUnit;
            } else {
                $penalty += $pointsPerUnit;
            }
        }

        return max(0, 100 - $penalty);
    }

    /**
     * @param  array<int, array{namespace: string, key: string, type: string, ownerType: string}>  $definitions
     * @param  array<int, array{id: string, metafields: list<array{namespace: string, key: string, value: string, type: string}>}>  $productResources
     * @param  array<int, array{id: string, metafields: list<array{namespace: string, key: string, value: string, type: string}>}>  $collectionResources
     * @return array<int, array{namespace: string, key: string, resource_type: string, issue_type: string, occurrences: int, details: array<mixed>|null}>
     */
    public function analyze(array $definitions, array $productResources, array $collectionResources): array
    {
        $issues = [];

        $issues = array_merge($issues, $this->checkDuplicateNamespace($definitions));
        $issues = array_merge($issues, $this->checkDefinitionWithoutValues($definitions, $productResources, $collectionResources));
        $issues = array_merge($issues, $this->checkValueWithoutDefinition($definitions, $productResources, $collectionResources));
        $issues = array_merge($issues, $this->checkEmptyMetafields($productResources, $collectionResources));
        $issues = array_merge($issues, $this->checkUnusedMetafield($definitions, $productResources, $collectionResources));
        $issues = array_merge($issues, $this->checkLongTextValues($productResources, $collectionResources));
        $issues = array_merge($issues, $this->checkSeoDuplicates($productResources));
        $issues = array_merge($issues, $this->checkMissingValidations($definitions));

        return $issues;
    }

    /**
     * Rule 1: Same namespace used across different resource types.
     *
     * @param  array<int, array{namespace: string, key: string, type: string, ownerType: string}>  $definitions
     * @return array<int, array{namespace: string, key: string, resource_type: string, issue_type: string, occurrences: int, details: array<mixed>|null}>
     */
    private function checkDuplicateNamespace(array $definitions): array
    {
        $namespaceOwnerTypes = [];

        foreach ($definitions as $def) {
            $namespaceOwnerTypes[$def['namespace']][] = $def['ownerType'];
        }

        $issues = [];

        foreach ($namespaceOwnerTypes as $namespace => $ownerTypes) {
            $unique = array_unique($ownerTypes);

            if (count($unique) > 1) {
                $issues[] = [
                    'namespace' => $namespace,
                    'key' => '*',
                    'resource_type' => ResourceType::Global->value,
                    'issue_type' => IssueType::DuplicateNamespace->value,
                    'occurrences' => count($unique),
                    'details' => ['owner_types' => array_values($unique)],
                ];
            }
        }

        return $issues;
    }

    /**
     * Rule 2: Definition exists but no resources have that field populated.
     *
     * @param  array<int, array{namespace: string, key: string, type: string, ownerType: string}>  $definitions
     * @param  array<int, array{id: string, metafields: list<array{namespace: string, key: string, value: string, type: string}>}>  $productResources
     * @param  array<int, array{id: string, metafields: list<array{namespace: string, key: string, value: string, type: string}>}>  $collectionResources
     * @return array<int, array{namespace: string, key: string, resource_type: string, issue_type: string, occurrences: int, details: array<mixed>|null}>
     */
    private function checkDefinitionWithoutValues(array $definitions, array $productResources, array $collectionResources): array
    {
        $allMetafieldKeys = $this->extractMetafieldKeys($productResources, $collectionResources);
        $issues = [];

        foreach ($definitions as $def) {
            $compositeKey = "{$def['namespace']}.{$def['key']}";

            if (! in_array($compositeKey, $allMetafieldKeys)) {
                $issues[] = [
                    'namespace' => $def['namespace'],
                    'key' => $def['key'],
                    'resource_type' => $this->ownerTypeToResourceType($def['ownerType']),
                    'issue_type' => IssueType::DefinitionWithoutValues->value,
                    'occurrences' => 1,
                    'details' => null,
                ];
            }
        }

        return $issues;
    }

    /**
     * Rule 3: Metafield value found without a corresponding definition.
     *
     * @param  array<int, array{namespace: string, key: string, type: string, ownerType: string}>  $definitions
     * @param  array<int, array{id: string, metafields: list<array{namespace: string, key: string, value: string, type: string}>}>  $productResources
     * @param  array<int, array{id: string, metafields: list<array{namespace: string, key: string, value: string, type: string}>}>  $collectionResources
     * @return array<int, array{namespace: string, key: string, resource_type: string, issue_type: string, occurrences: int, details: array<mixed>|null}>
     */
    private function checkValueWithoutDefinition(array $definitions, array $productResources, array $collectionResources): array
    {
        $definedKeys = array_map(
            fn ($def) => "{$def['namespace']}.{$def['key']}",
            $definitions
        );

        $issues = [];
        $seen = [];

        $this->iterateAllMetafields($productResources, ResourceType::Product, function ($mf, $resourceType) use ($definedKeys, &$issues, &$seen) {
            $compositeKey = "{$mf['namespace']}.{$mf['key']}";

            if (! in_array($compositeKey, $definedKeys) && ! isset($seen[$compositeKey])) {
                $seen[$compositeKey] = true;
                $issues[] = [
                    'namespace' => $mf['namespace'],
                    'key' => $mf['key'],
                    'resource_type' => $resourceType->value,
                    'issue_type' => IssueType::ValueWithoutDefinition->value,
                    'occurrences' => 1,
                    'details' => null,
                ];
            }
        });

        $this->iterateAllMetafields($collectionResources, ResourceType::Collection, function ($mf, $resourceType) use ($definedKeys, &$issues, &$seen) {
            $compositeKey = "{$mf['namespace']}.{$mf['key']}";

            if (! in_array($compositeKey, $definedKeys) && ! isset($seen[$compositeKey])) {
                $seen[$compositeKey] = true;
                $issues[] = [
                    'namespace' => $mf['namespace'],
                    'key' => $mf['key'],
                    'resource_type' => $resourceType->value,
                    'issue_type' => IssueType::ValueWithoutDefinition->value,
                    'occurrences' => 1,
                    'details' => null,
                ];
            }
        });

        return $issues;
    }

    /**
     * Rule 4: Value is null or empty string.
     *
     * @param  array<int, array{id: string, metafields: list<array{namespace: string, key: string, value: string, type: string}>}>  $productResources
     * @param  array<int, array{id: string, metafields: list<array{namespace: string, key: string, value: string, type: string}>}>  $collectionResources
     * @return array<int, array{namespace: string, key: string, resource_type: string, issue_type: string, occurrences: int, details: array<mixed>|null}>
     */
    private function checkEmptyMetafields(array $productResources, array $collectionResources): array
    {
        $counts = [];

        $checkResource = function (array $resources, ResourceType $resourceType) use (&$counts): void {
            $this->iterateAllMetafields($resources, $resourceType, function ($mf, $rt) use (&$counts) {
                if ($mf['value'] === null || $mf['value'] === '') {
                    $compositeKey = "{$mf['namespace']}.{$mf['key']}.{$rt->value}";
                    $counts[$compositeKey] = ($counts[$compositeKey] ?? [
                        'namespace' => $mf['namespace'],
                        'key' => $mf['key'],
                        'resource_type' => $rt->value,
                        'count' => 0,
                    ]);
                    $counts[$compositeKey]['count']++;
                }
            });
        };

        $checkResource($productResources, ResourceType::Product);
        $checkResource($collectionResources, ResourceType::Collection);

        $issues = [];

        foreach ($counts as $item) {
            $issues[] = [
                'namespace' => $item['namespace'],
                'key' => $item['key'],
                'resource_type' => $item['resource_type'],
                'issue_type' => IssueType::EmptyMetafield->value,
                'occurrences' => $item['count'],
                'details' => null,
            ];
        }

        return $issues;
    }

    /**
     * Rule 5: Definition with ownerType has 0 assignments across all resources.
     *
     * @param  array<int, array{namespace: string, key: string, type: string, ownerType: string}>  $definitions
     * @param  array<int, array{id: string, metafields: list<array{namespace: string, key: string, value: string, type: string}>}>  $productResources
     * @param  array<int, array{id: string, metafields: list<array{namespace: string, key: string, value: string, type: string}>}>  $collectionResources
     * @return array<int, array{namespace: string, key: string, resource_type: string, issue_type: string, occurrences: int, details: array<mixed>|null}>
     */
    private function checkUnusedMetafield(array $definitions, array $productResources, array $collectionResources): array
    {
        $usedKeys = [];

        $collectUsed = function (array $resources) use (&$usedKeys): void {
            foreach ($resources as $resource) {
                foreach ($resource['metafields'] as $mf) {
                    if ($mf['value'] !== null && $mf['value'] !== '') {
                        $usedKeys["{$mf['namespace']}.{$mf['key']}"] = true;
                    }
                }
            }
        };

        $collectUsed($productResources);
        $collectUsed($collectionResources);

        $issues = [];

        foreach ($definitions as $def) {
            $compositeKey = "{$def['namespace']}.{$def['key']}";

            if (! isset($usedKeys[$compositeKey])) {
                $issues[] = [
                    'namespace' => $def['namespace'],
                    'key' => $def['key'],
                    'resource_type' => $this->ownerTypeToResourceType($def['ownerType']),
                    'issue_type' => IssueType::UnusedMetafield->value,
                    'occurrences' => 1,
                    'details' => null,
                ];
            }
        }

        return $issues;
    }

    /**
     * Rule 6: Value string > 500 characters.
     *
     * @param  array<int, array{id: string, metafields: list<array{namespace: string, key: string, value: string, type: string}>}>  $productResources
     * @param  array<int, array{id: string, metafields: list<array{namespace: string, key: string, value: string, type: string}>}>  $collectionResources
     * @return array<int, array{namespace: string, key: string, resource_type: string, issue_type: string, occurrences: int, details: array<mixed>|null}>
     */
    private function checkLongTextValues(array $productResources, array $collectionResources): array
    {
        $counts = [];

        $checkResource = function (array $resources, ResourceType $resourceType) use (&$counts): void {
            $this->iterateAllMetafields($resources, $resourceType, function ($mf, $rt) use (&$counts) {
                if (is_string($mf['value']) && strlen($mf['value']) > self::LONG_TEXT_THRESHOLD) {
                    $compositeKey = "{$mf['namespace']}.{$mf['key']}.{$rt->value}";
                    $counts[$compositeKey] = $counts[$compositeKey] ?? [
                        'namespace' => $mf['namespace'],
                        'key' => $mf['key'],
                        'resource_type' => $rt->value,
                        'count' => 0,
                    ];
                    $counts[$compositeKey]['count']++;
                }
            });
        };

        $checkResource($productResources, ResourceType::Product);
        $checkResource($collectionResources, ResourceType::Collection);

        $issues = [];

        foreach ($counts as $item) {
            $issues[] = [
                'namespace' => $item['namespace'],
                'key' => $item['key'],
                'resource_type' => $item['resource_type'],
                'issue_type' => IssueType::LongTextValue->value,
                'occurrences' => $item['count'],
                'details' => null,
            ];
        }

        return $issues;
    }

    /**
     * Rule 7: Same value on >= 10 products for the same namespace.key.
     *
     * @param  array<int, array{id: string, metafields: list<array{namespace: string, key: string, value: string, type: string}>}>  $productResources
     * @return array<int, array{namespace: string, key: string, resource_type: string, issue_type: string, occurrences: int, details: array<mixed>|null}>
     */
    private function checkSeoDuplicates(array $productResources): array
    {
        /** @var array<string, array<string, int>> $valuesByKey */
        $valuesByKey = [];

        foreach ($productResources as $resource) {
            foreach ($resource['metafields'] as $mf) {
                if (! is_string($mf['value']) || $mf['value'] === '') {
                    continue;
                }

                $compositeKey = "{$mf['namespace']}.{$mf['key']}";
                $valuesByKey[$compositeKey][$mf['value']] = ($valuesByKey[$compositeKey][$mf['value']] ?? 0) + 1;
            }
        }

        $issues = [];

        foreach ($valuesByKey as $compositeKey => $valueCounts) {
            foreach ($valueCounts as $value => $count) {
                if ($count >= self::SEO_DUPLICATE_THRESHOLD) {
                    [$namespace, $key] = explode('.', $compositeKey, 2);

                    $issues[] = [
                        'namespace' => $namespace,
                        'key' => $key,
                        'resource_type' => ResourceType::Product->value,
                        'issue_type' => IssueType::SeoDuplicate->value,
                        'occurrences' => $count,
                        'details' => ['duplicate_value' => $value],
                    ];

                    break;
                }
            }
        }

        return $issues;
    }

    /**
     * Rule 8: Definition has no Shopify validation rules configured.
     *
     * @param  array<int, array{namespace: string, key: string, type: string, ownerType: string, validations: array<mixed>}>  $definitions
     * @return array<int, array{namespace: string, key: string, resource_type: string, issue_type: string, occurrences: int, details: array<mixed>|null}>
     */
    private function checkMissingValidations(array $definitions): array
    {
        $issues = [];

        foreach ($definitions as $def) {
            if (empty($def['validations'])) {
                $issues[] = [
                    'namespace' => $def['namespace'],
                    'key' => $def['key'],
                    'resource_type' => $this->ownerTypeToResourceType($def['ownerType']),
                    'issue_type' => IssueType::ValidationMissing->value,
                    'occurrences' => 1,
                    'details' => null,
                ];
            }
        }

        return $issues;
    }

    /**
     * @param  array<int, array{id: string, metafields: list<array{namespace: string, key: string, value: string, type: string}>}>  $productResources
     * @param  array<int, array{id: string, metafields: list<array{namespace: string, key: string, value: string, type: string}>}>  $collectionResources
     * @return list<string>
     */
    private function extractMetafieldKeys(array $productResources, array $collectionResources): array
    {
        $keys = [];

        foreach (array_merge($productResources, $collectionResources) as $resource) {
            foreach ($resource['metafields'] as $mf) {
                $keys[] = "{$mf['namespace']}.{$mf['key']}";
            }
        }

        return array_unique($keys);
    }

    /**
     * @param  array<int, array{id: string, metafields: list<array{namespace: string, key: string, value: string, type: string}>}>  $resources
     */
    private function iterateAllMetafields(array $resources, ResourceType $resourceType, callable $callback): void
    {
        foreach ($resources as $resource) {
            foreach ($resource['metafields'] as $mf) {
                $callback($mf, $resourceType);
            }
        }
    }

    private function ownerTypeToResourceType(string $ownerType): string
    {
        return match (strtolower($ownerType)) {
            'product' => ResourceType::Product->value,
            'productvariant' => ResourceType::Variant->value,
            'collection' => ResourceType::Collection->value,
            default => ResourceType::Global->value,
        };
    }
}
