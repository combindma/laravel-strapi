<?php

namespace Combindma\Strapi;

use Combindma\Strapi\DataObjects\ContactDto;
use Combindma\Strapi\DataObjects\FeatureDto;
use Combindma\Strapi\DataObjects\HeroDto;
use Combindma\Strapi\DataObjects\LogosDto;
use Combindma\Strapi\DataObjects\PageDto;
use Combindma\Strapi\DataObjects\ResponsiveImageDto;
use Combindma\Strapi\DataObjects\SectionDto;
use Combindma\Strapi\DataObjects\SeoDto;
use Combindma\Strapi\DataObjects\ServiceDto;
use Combindma\Strapi\DataObjects\ServicePageDto;
use Combindma\Strapi\DataObjects\SocialDto;
use GraphQL\Client;
use GraphQL\Query;
use GraphQL\RawObject;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
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

    public function page(string $documentId): PageDto
    {
        return $this->cache->rememberForever('page_'.$documentId, function () use ($documentId): PageDto {
            $gql = (new Query('page'))
                ->setArguments(['documentId' => new RawObject('"'.$documentId.'"')])
                ->setSelectionSet([
                    (new Query('hero'))->setSelectionSet([
                        'title',
                        'label',
                        'description',
                        'cta',
                        (new Query('image'))->setSelectionSet(['url', 'alternativeText', 'formats']),
                        (new Query('video'))->setSelectionSet(['url']),
                    ]),
                    (new Query('sections'))->setSelectionSet([
                        'title',
                        'label',
                        'content',
                        'bgColor',
                        (new Query('image'))->setSelectionSet(['url', 'alternativeText', 'formats']),
                        (new Query('video'))->setSelectionSet(['url']),
                        (new Query('features'))
                            ->setArguments([
                                'pagination' => new RawObject('{ limit: 30 }'),
                            ])
                            ->setSelectionSet([
                                'title',
                                'label',
                                'description',
                                'width',
                                (new Query('image'))->setSelectionSet(['url', 'alternativeText', 'formats']),
                            ]),
                    ]),
                ]);

            $attributes = $this->query($gql)->page;

            return new PageDto(
                new HeroDto(
                    $attributes->hero?->title,
                    $attributes->hero?->label,
                    $attributes->hero?->description,
                    $attributes->hero?->cta,
                    $this->getResponsiveImage($attributes->hero?->image),
                    $attributes->hero?->video?->url,
                ),
                $attributes->sections ? $this->formatSections($attributes->sections) : null,
            );
        });
    }

    public function socialNetworks(): SocialDto
    {
        return $this->cache->rememberForever('social_', function (): SocialDto {
            $gql = (new Query('social'))
                ->setSelectionSet([
                    'facebook',
                    'linkedin',
                    'instagram',
                    'twitter',
                ]);

            $attributes = $this->query($gql)->social;

            return new SocialDto(
                $attributes->facebook,
                $attributes->linkedin,
                $attributes->instagram,
                $attributes->twitter,
            );
        });
    }

    public function privacy(): ?string
    {
        return $this->cache->rememberForever('privacy_', function (): ?string {
            $gql = (new Query('privacy'))
                ->setSelectionSet(['content']);

            return $this->query($gql)->privacy?->content;
        });
    }

    public function contactInfos(): ContactDto
    {
        return $this->cache->rememberForever('contact_', function (): ContactDto {
            $gql = (new Query('contact'))
                ->setSelectionSet([
                    'email',
                    'phone',
                    'address',
                ]);

            $attributes = $this->query($gql)->contact;

            return new ContactDto(
                $attributes->email,
                $attributes->phone,
                $attributes->address,
            );
        });
    }

    public function services(): Collection
    {
        return $this->cache->tags(['service'])->rememberForever('services', function (): Collection {
            $gql = (new Query('services'))
                ->setArguments([
                    'sort' => new RawObject('"createdAt:asc"'),
                ])
                ->setSelectionSet([
                    'title',
                    'slug',
                    'publishedAt',
                    'updatedAt',
                    (new Query('hero'))->setSelectionSet([
                        (new Query('image'))->setSelectionSet(['url', 'alternativeText', 'formats']),
                    ]),
                ]);

            $data = $this->query($gql)->services;

            $attributes = [];
            foreach ($data as $service) {
                $attributes[] = new ServiceDto(
                    $service->title,
                    $service->slug,
                    $this->getResponsiveImage($service->hero?->image),
                    Carbon::make($service->publishedAt),
                    Carbon::make($service->updatedAt),
                );
            }

            return collect($attributes);
        });
    }

    public function service(string $slug): ServicePageDto
    {
        return $this->cache->rememberForever('service_'.$slug, function () use ($slug): ServicePageDto {
            $gql = (new Query('services'))
                ->setArguments([
                    'filters' => new RawObject('{ slug: { eq: "'.$slug.'" } },'),
                    'pagination' => new RawObject('{ limit: 1 }'),
                ])
                ->setSelectionSet([
                    'title',
                    'slug',
                    'publishedAt',
                    'updatedAt',
                    (new Query('hero'))->setSelectionSet([
                        'title',
                        'label',
                        'description',
                        'cta',
                        (new Query('image'))->setSelectionSet(['url', 'alternativeText', 'formats']),
                        (new Query('video'))->setSelectionSet(['url']),
                    ]),
                    (new Query('logos'))->setSelectionSet([
                        'title',
                        (new Query('media'))
                            ->setArguments([
                                'pagination' => new RawObject('{ limit: 16 }'),
                            ])
                            ->setSelectionSet([
                                (new Query('image'))->setSelectionSet(['url', 'alternativeText', 'formats']),
                            ]),
                    ]),
                    (new Query('sections'))->setSelectionSet([
                        'title',
                        'label',
                        'content',
                        (new Query('image'))->setSelectionSet(['url', 'alternativeText', 'formats']),
                        (new Query('video'))->setSelectionSet(['url']),
                        (new Query('features'))->setSelectionSet([
                            'title',
                            'label',
                            'description',
                            'width',
                            (new Query('image'))->setSelectionSet(['url', 'alternativeText', 'formats']),
                        ]),
                    ]),
                    (new Query('seo'))->setSelectionSet([
                        'metaTitle',
                        'metaDescription',
                        'noIndex',
                        (new Query('metaImage'))->setSelectionSet(['url']),
                    ]),
                ]);

            $data = $this->query($gql)->services;

            if (empty($data)) {
                abort(404);
            }

            $service = $data[0];

            return new ServicePageDto(
                $service->title,
                $service->slug,
                new HeroDto(
                    $service->hero->title,
                    $service->hero->label,
                    $service->hero->description,
                    $service->hero->cta,
                    $this->getResponsiveImage($service->hero->image),
                    $service->hero->video?->url,
                ),
                new LogosDto(
                    $service->logos->title,
                    $this->getMediaUrl($service->logos->media),
                ),
                $this->formatSections($service->sections),
                new SeoDto(
                    $service->seo->metaTitle,
                    $service->seo->metaDescription,
                    $service->seo->metaImage?->url,
                    (bool) $service->seo->noIndex,
                ),
                Carbon::make($service->publishedAt),
                Carbon::make($service->updatedAt),
            );
        });
    }

    public function clearModel(string $model, ?string $documentId, ?string $slug): void
    {
        $normalizedModel = $this->normalizeModel($model);

        foreach ($this->cacheKeysForModel($normalizedModel, $documentId, $slug) as $cacheKey) {
            $this->cache->forget($cacheKey);
        }

        if ($normalizedModel === 'page' && $documentId !== null) {
            $this->cache->forget('seo_'.$documentId);
        }

        $this->cache->tags([$normalizedModel])->flush();
    }

    protected function normalizeModel(string $model): string
    {
        return match ($model) {
            'services' => 'service',
            default => $model,
        };
    }

    /**
     * @return array<int, string>
     */
    protected function cacheKeysForModel(string $model, ?string $documentId, ?string $slug): array
    {
        $cacheKeys = [];

        if ($documentId !== null) {
            $cacheKeys[] = $this->cacheKeyForIdentifier($model, $documentId);
        }

        if ($slug !== null) {
            $cacheKeys[] = $this->cacheKeyForIdentifier($model, $slug);
        }

        if ($cacheKeys !== []) {
            return array_values(array_unique($cacheKeys));
        }

        return [$this->baseCacheKey($model)];
    }

    protected function cacheKeyForIdentifier(string $model, string $identifier): string
    {
        return match ($model) {
            'service' => 'service_'.$identifier,
            default => $model.'_'.$identifier,
        };
    }

    protected function baseCacheKey(string $model): string
    {
        return match ($model) {
            'social', 'privacy', 'contact' => $model.'_',
            'service' => 'services',
            default => $model,
        };
    }

    public function getResponsiveImage(?object $attributes = null): ResponsiveImageDto
    {
        if (empty($attributes)) {
            return new ResponsiveImageDto(
                null,
                null,
                null,
            );
        }

        return new ResponsiveImageDto(
            $attributes->url,
            $attributes->alternativeText,
            $this->generateSrcset($attributes),
        );

    }

    public function generateSrcset($attributes = null): string
    {
        $formats = (array) $attributes?->formats;

        $srcsetValues[] = sprintf('%s 2000w', $attributes?->url);
        foreach ($formats as $key => $format) {
            if (in_array($key, ['large', 'medium', 'small', 'thumbnail'])) {
                $srcsetValues[] = sprintf('%s %dw', $format->url, $format->width);
            }
        }

        return implode(', ', $srcsetValues);
    }

    public function getMedia(array $gallery): array
    {
        $media = [];
        foreach ($gallery as $item) {
            $media[] = $this->getResponsiveImage($item->image);
        }

        return $media;
    }

    public function getMediaUrl(array $gallery): array
    {
        $media = [];
        foreach ($gallery as $item) {
            $media[] = $item->image->url;
        }

        return $media;
    }

    public function formatSections($sections): Collection
    {
        return collect($sections)->map(fn ($section) => new SectionDto(
            $section->title,
            $section->label,
            $section->content,
            $this->getResponsiveImage($section->image),
            $section->video?->url,
            empty($section->features) ? null : collect($section->features)->map(fn ($feature) => new FeatureDto(
                $feature->title,
                $feature->label,
                $feature->description,
                $this->getResponsiveImage($feature->image),
            )),
        ));
    }
}
