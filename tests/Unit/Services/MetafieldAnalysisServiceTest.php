<?php

use DoctorStore\Core\Enums\IssueType;
use DoctorStore\Core\Enums\ResourceType;
use DoctorStore\Core\Services\MetafieldAnalysisService;

beforeEach(function () {
    $this->service = new MetafieldAnalysisService();
});

// ─── Helpers ────────────────────────────────────────────────────────────────

function makeDefinition(string $namespace, string $key, string $ownerType = 'PRODUCT', array $validations = []): array
{
    return ['namespace' => $namespace, 'key' => $key, 'type' => 'single_line_text_field', 'ownerType' => $ownerType, 'validations' => $validations];
}

function makeProduct(string $id, array $metafields): array
{
    return ['id' => $id, 'metafields' => $metafields];
}

function makeMetafield(string $namespace, string $key, string $value = 'some-value'): array
{
    return ['namespace' => $namespace, 'key' => $key, 'value' => $value, 'type' => 'single_line_text_field'];
}

// ─── Rule 1: DuplicateNamespace ──────────────────────────────────────────────

test('detects duplicate namespace used across different resource types', function () {
    $definitions = [
        makeDefinition('custom', 'color', 'PRODUCT'),
        makeDefinition('custom', 'width', 'COLLECTION'),
    ];

    $issues = $this->service->analyze($definitions, [], []);

    $duplicateIssue = collect($issues)->firstWhere('issue_type', IssueType::DuplicateNamespace->value);
    expect($duplicateIssue)->not->toBeNull();
    expect($duplicateIssue['namespace'])->toBe('custom');
});

test('does not flag namespace used on a single resource type', function () {
    $definitions = [
        makeDefinition('custom', 'color', 'PRODUCT'),
        makeDefinition('custom', 'size', 'PRODUCT'),
    ];

    $issues = $this->service->analyze($definitions, [], []);

    $duplicateIssues = collect($issues)->where('issue_type', IssueType::DuplicateNamespace->value);
    expect($duplicateIssues)->toBeEmpty();
});

// ─── Rule 2: DefinitionWithoutValues ─────────────────────────────────────────

test('detects definition with no product values', function () {
    $definitions = [makeDefinition('custom', 'color')];
    $products = [makeProduct('gid://1', [])];

    $issues = $this->service->analyze($definitions, $products, []);

    $issue = collect($issues)->firstWhere('issue_type', IssueType::DefinitionWithoutValues->value);
    expect($issue)->not->toBeNull();
    expect($issue['namespace'])->toBe('custom');
    expect($issue['key'])->toBe('color');
});

test('does not flag definition when at least one product has the value', function () {
    $definitions = [makeDefinition('custom', 'color')];
    $products = [makeProduct('gid://1', [makeMetafield('custom', 'color', 'red')])];

    $issues = $this->service->analyze($definitions, $products, []);

    $definitionIssues = collect($issues)->where('issue_type', IssueType::DefinitionWithoutValues->value);
    expect($definitionIssues)->toBeEmpty();
});

// ─── Rule 3: ValueWithoutDefinition ──────────────────────────────────────────

test('detects value without a matching definition', function () {
    $products = [makeProduct('gid://1', [makeMetafield('orphan', 'tag', 'hello')])];

    $issues = $this->service->analyze([], $products, []);

    $issue = collect($issues)->firstWhere('issue_type', IssueType::ValueWithoutDefinition->value);
    expect($issue)->not->toBeNull();
    expect($issue['namespace'])->toBe('orphan');
    expect($issue['key'])->toBe('tag');
});

test('does not flag value that has a matching definition', function () {
    $definitions = [makeDefinition('custom', 'color')];
    $products = [makeProduct('gid://1', [makeMetafield('custom', 'color', 'blue')])];

    $issues = $this->service->analyze($definitions, $products, []);

    $valueIssues = collect($issues)->where('issue_type', IssueType::ValueWithoutDefinition->value);
    expect($valueIssues)->toBeEmpty();
});

// ─── Rule 4: EmptyMetafield ───────────────────────────────────────────────────

test('detects empty string metafield values', function () {
    $products = [makeProduct('gid://1', [makeMetafield('custom', 'color', '')])];

    $issues = $this->service->analyze([], $products, []);

    $issue = collect($issues)->firstWhere('issue_type', IssueType::EmptyMetafield->value);
    expect($issue)->not->toBeNull();
    expect($issue['occurrences'])->toBe(1);
});

test('counts occurrences across multiple products for empty metafields', function () {
    $products = [
        makeProduct('gid://1', [makeMetafield('custom', 'color', '')]),
        makeProduct('gid://2', [makeMetafield('custom', 'color', '')]),
    ];

    $issues = $this->service->analyze([], $products, []);

    $issue = collect($issues)->firstWhere('issue_type', IssueType::EmptyMetafield->value);
    expect($issue['occurrences'])->toBe(2);
});

// ─── Rule 5: UnusedMetafield ──────────────────────────────────────────────────

test('detects unused definition with zero non-empty assignments', function () {
    $definitions = [makeDefinition('custom', 'color')];

    $issues = $this->service->analyze($definitions, [], []);

    $issue = collect($issues)->firstWhere('issue_type', IssueType::UnusedMetafield->value);
    expect($issue)->not->toBeNull();
});

test('does not flag definition that has at least one non-empty assignment', function () {
    $definitions = [makeDefinition('custom', 'color')];
    $products = [makeProduct('gid://1', [makeMetafield('custom', 'color', 'red')])];

    $issues = $this->service->analyze($definitions, $products, []);

    $unusedIssues = collect($issues)->where('issue_type', IssueType::UnusedMetafield->value);
    expect($unusedIssues)->toBeEmpty();
});

// ─── Rule 6: LongTextValue ────────────────────────────────────────────────────

test('detects metafield value longer than 500 characters', function () {
    $longValue = str_repeat('a', 501);
    $products = [makeProduct('gid://1', [makeMetafield('custom', 'description', $longValue)])];

    $issues = $this->service->analyze([], $products, []);

    $issue = collect($issues)->firstWhere('issue_type', IssueType::LongTextValue->value);
    expect($issue)->not->toBeNull();
    expect($issue['namespace'])->toBe('custom');
    expect($issue['key'])->toBe('description');
});

test('does not flag metafield value at or below 500 characters', function () {
    $exactValue = str_repeat('a', 500);
    $products = [makeProduct('gid://1', [makeMetafield('custom', 'description', $exactValue)])];

    $issues = $this->service->analyze([], $products, []);

    $longIssues = collect($issues)->where('issue_type', IssueType::LongTextValue->value);
    expect($longIssues)->toBeEmpty();
});

// ─── Rule 7: SeoDuplicate ─────────────────────────────────────────────────────

test('detects same value on 10 or more products for the same namespace.key', function () {
    $products = collect(range(1, 10))
        ->map(fn ($i) => makeProduct("gid://{$i}", [makeMetafield('seo', 'title', 'Generic Title')]))
        ->toArray();

    $issues = $this->service->analyze([], $products, []);

    $issue = collect($issues)->firstWhere('issue_type', IssueType::SeoDuplicate->value);
    expect($issue)->not->toBeNull();
    expect($issue['occurrences'])->toBe(10);
    expect($issue['details']['duplicate_value'])->toBe('Generic Title');
});

test('does not flag same value on fewer than 10 products', function () {
    $products = collect(range(1, 9))
        ->map(fn ($i) => makeProduct("gid://{$i}", [makeMetafield('seo', 'title', 'Generic Title')]))
        ->toArray();

    $issues = $this->service->analyze([], $products, []);

    $seoIssues = collect($issues)->where('issue_type', IssueType::SeoDuplicate->value);
    expect($seoIssues)->toBeEmpty();
});

// ─── Rule 8: ValidationMissing ────────────────────────────────────────────────

test('detects definition with no validations configured', function () {
    $definitions = [makeDefinition('custom', 'size', 'PRODUCT', [])];

    $issues = $this->service->analyze($definitions, [], []);

    $issue = collect($issues)->firstWhere('issue_type', IssueType::ValidationMissing->value);
    expect($issue)->not->toBeNull();
    expect($issue['namespace'])->toBe('custom');
    expect($issue['key'])->toBe('size');
});

test('does not flag definition that has validations configured', function () {
    $definitions = [makeDefinition('custom', 'size', 'PRODUCT', [['name' => 'min', 'value' => '1']])];

    $issues = $this->service->analyze($definitions, [], []);

    $validationIssues = collect($issues)->where('issue_type', IssueType::ValidationMissing->value);
    expect($validationIssues)->toBeEmpty();
});
