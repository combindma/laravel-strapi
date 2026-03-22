<?php

namespace Combindma\Strapi;

use Combindma\Strapi\DataObjects\SeoDto;
use GraphQL\Client;
use GraphQL\Query;
use GraphQL\RawObject;
use Illuminate\Contracts\Cache\Repository;
use InvalidArgumentException;

class Strapi
{
    public function __construct(
        protected Client $client,
        protected Repository $cache,
    ) {}

    public function query(Query $query, bool $resultsAsArray = false, array $variables = []): object|array
    {
        return $this->client->runQuery($query, $resultsAsArray, $variables)->getData();
    }

    public static function makeClient(string $graphqlUrl, ?string $token = null, int $timeout = 30): Client
    {
        if ($graphqlUrl === '') {
            throw new InvalidArgumentException('The Strapi GraphQL URL is not configured.');
        }

        $headers = [
            'Accept' => 'application/json',
        ];

        if ($token !== null && $token !== '') {
            $headers['Authorization'] = 'Bearer '.$token;
        }

        return new Client($graphqlUrl, $headers, [
            'timeout' => $timeout,
        ]);
    }

    public function seo(string $documentId): SeoDto
    {
        return $this->cache->rememberForever('seo_'.$documentId, function () use ($documentId): SeoDto {
            $gql = new Query('page');
            $gql->setArguments(['documentId' => new RawObject('"'.$documentId.'"')]);
            $gql->setSelectionSet([
                (new Query('seo'))->setSelectionSet([
                    'metaTitle',
                    'metaDescription',
                    'noIndex',
                    (new Query('metaImage'))->setSelectionSet(['url']),
                ]),
            ]);

            $attributes = $this->query($gql)->page;

            return new SeoDto(
                $attributes->seo->metaTitle,
                $attributes->seo->metaDescription,
                $attributes->seo->metaImage?->url,
                (bool) $attributes->seo->noIndex,
            );
        });
    }
}
