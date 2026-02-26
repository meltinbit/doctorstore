<?php

namespace App\Services;

use App\Models\ShopifyStore;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ShopifyGraphQLService
{
    public function __construct(private ShopifyStore $store) {}

    public function query(string $gql, array $variables = []): array
    {
        $response = $this->client()->post('graphql.json', [
            'query' => $gql,
            'variables' => $variables,
        ]);

        if ($response->failed()) {
            throw new RuntimeException("Shopify GraphQL request failed: {$response->status()}");
        }

        $data = $response->json();

        if (! empty($data['errors'])) {
            $message = collect($data['errors'])->pluck('message')->implode(', ');
            throw new RuntimeException("Shopify GraphQL errors: {$message}");
        }

        return $data['data'] ?? [];
    }

    /**
     * @return array<int, array{namespace: string, key: string, type: string, ownerType: string}>
     */
    public function getMetafieldDefinitions(): array
    {
        $gql = <<<'GQL'
        query($ownerType: MetafieldOwnerType!, $cursor: String) {
            metafieldDefinitions(ownerType: $ownerType, first: 250, after: $cursor) {
                nodes {
                    namespace
                    key
                    type { name }
                    ownerType
                    validations { name value }
                }
                pageInfo { hasNextPage endCursor }
            }
        }
        GQL;

        $ownerTypes = ['PRODUCT', 'PRODUCTVARIANT', 'COLLECTION', 'SHOP'];
        $definitions = [];

        foreach ($ownerTypes as $ownerType) {
            $cursor = null;

            do {
                $data = $this->query($gql, ['ownerType' => $ownerType, 'cursor' => $cursor]);
                $connection = $data['metafieldDefinitions'];

                foreach ($connection['nodes'] as $node) {
                    $definitions[] = [
                        'namespace' => $node['namespace'],
                        'key' => $node['key'],
                        'type' => $node['type']['name'],
                        'ownerType' => $node['ownerType'],
                        'validations' => $node['validations'] ?? [],
                    ];
                }

                $hasNextPage = $connection['pageInfo']['hasNextPage'];
                $cursor = $connection['pageInfo']['endCursor'];
            } while ($hasNextPage);
        }

        return $definitions;
    }

    /**
     * @return array<int, array{id: string, metafields: array<int, array{namespace: string, key: string, value: string, type: string}>}>
     */
    public function getProductsWithMetafields(): array
    {
        $gql = <<<'GQL'
        query($cursor: String) {
            products(first: 50, after: $cursor) {
                nodes {
                    id
                    metafields(first: 50) {
                        nodes { namespace key value type }
                    }
                }
                pageInfo { hasNextPage endCursor }
            }
        }
        GQL;

        return $this->paginateResourceWithMetafields($gql, 'products');
    }

    /**
     * @return array<int, array{id: string, metafields: array<int, array{namespace: string, key: string, value: string, type: string}>}>
     */
    public function getCollectionsWithMetafields(): array
    {
        $gql = <<<'GQL'
        query($cursor: String) {
            collections(first: 50, after: $cursor) {
                nodes {
                    id
                    metafields(first: 50) {
                        nodes { namespace key value type }
                    }
                }
                pageInfo { hasNextPage endCursor }
            }
        }
        GQL;

        return $this->paginateResourceWithMetafields($gql, 'collections');
    }

    /**
     * @return array<int, array{id: string, metafields: list<array{namespace: string, key: string, value: string, type: string}>}>
     */
    private function paginateResourceWithMetafields(string $gql, string $resourceKey): array
    {
        $resources = [];
        $cursor = null;

        do {
            $data = $this->query($gql, ['cursor' => $cursor]);
            $connection = $data[$resourceKey];

            foreach ($connection['nodes'] as $node) {
                $resources[] = [
                    'id' => $node['id'],
                    'metafields' => $node['metafields']['nodes'],
                ];
            }

            $hasNextPage = $connection['pageInfo']['hasNextPage'];
            $cursor = $connection['pageInfo']['endCursor'];
        } while ($hasNextPage);

        return $resources;
    }

    private function client(): PendingRequest
    {
        $version = config('shopify.api_version');

        return Http::baseUrl("https://{$this->store->shop_domain}/admin/api/{$version}/")
            ->withHeader('X-Shopify-Access-Token', $this->store->access_token)
            ->acceptJson()
            ->asJson();
    }
}
