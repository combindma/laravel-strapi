<?php

use Combindma\Strapi\DataObjects\ContactDto;
use Combindma\Strapi\DataObjects\SeoDto;
use Combindma\Strapi\DataObjects\ServiceDto;
use Combindma\Strapi\DataObjects\ServicePageDto;
use Combindma\Strapi\DataObjects\SocialDto;
use Combindma\Strapi\Facades\Strapi as StrapiFacade;
use Combindma\Strapi\Strapi;
use GraphQL\Client;
use GraphQL\Query;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Contracts\Cache\Repository;
use Symfony\Component\HttpKernel\Exception\HttpException;

afterEach(function () {
    Mockery::close();
});

it('builds a graphql client from configuration', function () {
    $client = Strapi::makeClient('https://example.com/graphql', 'secret-token', 45);

    expect($client)->toBeInstanceOf(Client::class);
});

it('throws a clear exception when the graphql url is missing', function () {
    expect(fn () => Strapi::makeClient(''))
        ->toThrow(InvalidArgumentException::class, 'The Strapi GraphQL URL is not configured.');
});

it('registers the strapi service in the container', function () {
    config()->set('strapi.graphql_url', 'https://example.com/graphql');

    expect(app(Strapi::class))->toBeInstanceOf(Strapi::class);
});

it('runs graphql queries and returns the data payload', function () {
    $mockHandler = new MockHandler([
        new Response(200, [], json_encode([
            'data' => [
                'page' => [
                    'title' => 'Services',
                ],
            ],
        ])),
    ]);

    $handler = HandlerStack::create($mockHandler);
    $container = [];
    $handler->push(Middleware::history($container));

    $service = new Strapi(
        new Client(
            'https://example.com/graphql',
            [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer secret-token',
            ],
            ['handler' => $handler, 'timeout' => 12],
            new GuzzleClient(['handler' => $handler]),
        ),
        app('cache.store'),
    );

    $data = $service->query((new Query('page'))->setSelectionSet(['title']));

    expect($data->page->title)->toBe('Services');

    /** @var Request $request */
    $request = $container[0]['request'];

    expect($request->getHeader('Authorization'))->toBe(['Bearer secret-token'])
        ->and($request->getHeader('Accept'))->toBe(['application/json']);
});

it('returns cached seo data through the facade', function () {
    $mockHandler = new MockHandler([
        new Response(200, [], json_encode([
            'data' => [
                'page' => [
                    'seo' => [
                        'metaTitle' => 'SEO Title',
                        'metaDescription' => 'SEO Description',
                        'noIndex' => true,
                        'metaImage' => [
                            'url' => 'https://cdn.example.com/seo.jpg',
                        ],
                    ],
                ],
            ],
        ])),
    ]);

    $service = new Strapi(
        new Client(
            'https://example.com/graphql',
            [],
            ['handler' => HandlerStack::create($mockHandler)],
            new GuzzleClient(['handler' => HandlerStack::create($mockHandler)]),
        ),
        app('cache.store'),
    );

    app()->instance(Strapi::class, $service);

    $first = StrapiFacade::seo('home');
    $second = StrapiFacade::seo('home');

    expect($first)->toBeInstanceOf(SeoDto::class)
        ->and($first->metaTitle)->toBe('SEO Title')
        ->and($first->metaDescription)->toBe('SEO Description')
        ->and($first->metaImage)->toBe('https://cdn.example.com/seo.jpg')
        ->and($first->noIndex)->toBeTrue()
        ->and($second)->toEqual($first);
});

it('returns cached page data with hero and sections', function () {
    $response = json_encode([
        'data' => [
            'page' => [
                'hero' => [
                    'title' => 'Home',
                    'label' => 'Welcome',
                    'description' => 'Landing page hero',
                    'cta' => 'Start now',
                    'image' => [
                        'url' => 'https://cdn.example.com/hero.jpg',
                        'alternativeText' => 'Hero image',
                        'formats' => (object) [
                            'medium' => [
                                'url' => 'https://cdn.example.com/hero-medium.jpg',
                                'width' => 768,
                            ],
                        ],
                    ],
                    'video' => [
                        'url' => 'https://cdn.example.com/hero.mp4',
                    ],
                ],
                'sections' => [
                    [
                        'title' => 'Section title',
                        'label' => 'Section label',
                        'content' => 'Section content',
                        'bgColor' => '#ffffff',
                        'image' => [
                            'url' => 'https://cdn.example.com/section.jpg',
                            'alternativeText' => 'Section image',
                            'formats' => (object) [],
                        ],
                        'video' => [
                            'url' => 'https://cdn.example.com/section.mp4',
                        ],
                        'features' => [
                            [
                                'title' => 'Feature title',
                                'label' => 'Feature label',
                                'description' => 'Feature description',
                                'width' => 'half',
                                'image' => [
                                    'url' => 'https://cdn.example.com/feature.jpg',
                                    'alternativeText' => 'Feature image',
                                    'formats' => (object) [],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $mockHandler = new MockHandler([
        new Response(200, [], $response),
    ]);

    $handler = HandlerStack::create($mockHandler);
    $container = [];
    $handler->push(Middleware::history($container));

    $service = new Strapi(
        new Client(
            'https://example.com/graphql',
            [],
            ['handler' => $handler],
            new GuzzleClient(['handler' => $handler]),
        ),
        app('cache.store'),
    );

    $first = $service->page('home-page');
    $second = $service->page('home-page');

    expect($first->hero?->title)->toBe('Home')
        ->and($first->hero?->image->url)->toBe('https://cdn.example.com/hero.jpg')
        ->and($first->hero?->image->srcset)->toContain('https://cdn.example.com/hero-medium.jpg 768w')
        ->and($first->sections)->not->toBeNull()
        ->and($first->sections?->count())->toBe(1)
        ->and($first->sections?->first()->title)->toBe('Section title')
        ->and($first->sections?->first()->features)->not->toBeNull()
        ->and($first->sections?->first()->features?->first()->title)->toBe('Feature title')
        ->and($second)->toEqual($first)
        ->and($container)->toHaveCount(1);
});

it('returns cached social networks data', function () {
    $mockHandler = new MockHandler([
        new Response(200, [], json_encode([
            'data' => [
                'social' => [
                    'facebook' => 'https://facebook.com/combind',
                    'linkedin' => 'https://linkedin.com/company/combind',
                    'instagram' => 'https://instagram.com/combind',
                    'twitter' => 'https://x.com/combind',
                ],
            ],
        ])),
    ]);

    $handler = HandlerStack::create($mockHandler);
    $container = [];
    $handler->push(Middleware::history($container));

    $service = new Strapi(
        new Client(
            'https://example.com/graphql',
            [],
            ['handler' => $handler],
            new GuzzleClient(['handler' => $handler]),
        ),
        app('cache.store'),
    );

    $first = $service->socialNetworks();
    $second = $service->socialNetworks();

    expect($first)->toBeInstanceOf(SocialDto::class)
        ->and($first->facebook)->toBe('https://facebook.com/combind')
        ->and($first->linkedin)->toBe('https://linkedin.com/company/combind')
        ->and($first->instagram)->toBe('https://instagram.com/combind')
        ->and($first->twitter)->toBe('https://x.com/combind')
        ->and($second)->toEqual($first)
        ->and($container)->toHaveCount(1);
});

it('returns cached privacy content', function () {
    $mockHandler = new MockHandler([
        new Response(200, [], json_encode([
            'data' => [
                'privacy' => [
                    'content' => '<p>Privacy policy</p>',
                ],
            ],
        ])),
    ]);

    $handler = HandlerStack::create($mockHandler);
    $container = [];
    $handler->push(Middleware::history($container));

    $service = new Strapi(
        new Client(
            'https://example.com/graphql',
            [],
            ['handler' => $handler],
            new GuzzleClient(['handler' => $handler]),
        ),
        app('cache.store'),
    );

    $first = $service->privacy();
    $second = $service->privacy();

    expect($first)->toBe('<p>Privacy policy</p>')
        ->and($second)->toBe($first)
        ->and($container)->toHaveCount(1);
});

it('returns cached contact information', function () {
    $mockHandler = new MockHandler([
        new Response(200, [], json_encode([
            'data' => [
                'contact' => [
                    'email' => 'hello@example.com',
                    'phone' => '+212600000000',
                    'address' => 'Casablanca',
                ],
            ],
        ])),
    ]);

    $handler = HandlerStack::create($mockHandler);
    $container = [];
    $handler->push(Middleware::history($container));

    $service = new Strapi(
        new Client(
            'https://example.com/graphql',
            [],
            ['handler' => $handler],
            new GuzzleClient(['handler' => $handler]),
        ),
        app('cache.store'),
    );

    $first = $service->contactInfos();
    $second = $service->contactInfos();

    expect($first)->toBeInstanceOf(ContactDto::class)
        ->and($first->email)->toBe('hello@example.com')
        ->and($first->phone)->toBe('+212600000000')
        ->and($first->address)->toBe('Casablanca')
        ->and($second)->toEqual($first)
        ->and($container)->toHaveCount(1);
});

it('returns cached services list', function () {
    $mockHandler = new MockHandler([
        new Response(200, [], json_encode([
            'data' => [
                'services' => [
                    [
                        'title' => 'Strategy',
                        'slug' => 'strategy',
                        'publishedAt' => '2026-01-10T08:00:00+00:00',
                        'updatedAt' => '2026-01-12T09:00:00+00:00',
                        'hero' => [
                            'image' => [
                                'url' => 'https://cdn.example.com/service.jpg',
                                'alternativeText' => 'Service image',
                                'formats' => (object) [],
                            ],
                        ],
                    ],
                ],
            ],
        ])),
    ]);

    $handler = HandlerStack::create($mockHandler);
    $container = [];
    $handler->push(Middleware::history($container));

    $service = new Strapi(
        new Client(
            'https://example.com/graphql',
            [],
            ['handler' => $handler],
            new GuzzleClient(['handler' => $handler]),
        ),
        app('cache.store'),
    );

    $first = $service->services();
    $second = $service->services();

    expect($first)->toHaveCount(1)
        ->and($first->first())->toBeInstanceOf(ServiceDto::class)
        ->and($first->first()->title)->toBe('Strategy')
        ->and($first->first()->slug)->toBe('strategy')
        ->and($first->first()->image->url)->toBe('https://cdn.example.com/service.jpg')
        ->and($first->first()->publishedAt->toIso8601String())->toBe('2026-01-10T08:00:00+00:00')
        ->and($second)->toEqual($first)
        ->and($container)->toHaveCount(1);
});

it('returns cached service details', function () {
    $mockHandler = new MockHandler([
        new Response(200, [], json_encode([
            'data' => [
                'services' => [
                    [
                        'title' => 'Strategy',
                        'slug' => 'strategy',
                        'publishedAt' => '2026-01-10T08:00:00+00:00',
                        'updatedAt' => '2026-01-12T09:00:00+00:00',
                        'hero' => [
                            'title' => 'Strategy Hero',
                            'label' => 'Advisory',
                            'description' => 'Service hero description',
                            'cta' => 'Talk to us',
                            'image' => [
                                'url' => 'https://cdn.example.com/hero.jpg',
                                'alternativeText' => 'Hero image',
                                'formats' => (object) [],
                            ],
                            'video' => [
                                'url' => 'https://cdn.example.com/hero.mp4',
                            ],
                        ],
                        'logos' => [
                            'title' => 'Trusted by',
                            'media' => [
                                [
                                    'image' => [
                                        'url' => 'https://cdn.example.com/logo-1.jpg',
                                        'alternativeText' => 'Logo 1',
                                        'formats' => (object) [],
                                    ],
                                ],
                                [
                                    'image' => [
                                        'url' => 'https://cdn.example.com/logo-2.jpg',
                                        'alternativeText' => 'Logo 2',
                                        'formats' => (object) [],
                                    ],
                                ],
                            ],
                        ],
                        'sections' => [
                            [
                                'title' => 'Section title',
                                'label' => 'Section label',
                                'content' => 'Section content',
                                'image' => [
                                    'url' => 'https://cdn.example.com/section.jpg',
                                    'alternativeText' => 'Section image',
                                    'formats' => (object) [],
                                ],
                                'video' => [
                                    'url' => 'https://cdn.example.com/section.mp4',
                                ],
                                'features' => [
                                    [
                                        'title' => 'Feature title',
                                        'label' => 'Feature label',
                                        'description' => 'Feature description',
                                        'width' => 'half',
                                        'image' => [
                                            'url' => 'https://cdn.example.com/feature.jpg',
                                            'alternativeText' => 'Feature image',
                                            'formats' => (object) [],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'seo' => [
                            'metaTitle' => 'Strategy SEO',
                            'metaDescription' => 'Strategy description',
                            'noIndex' => false,
                            'metaImage' => [
                                'url' => 'https://cdn.example.com/seo.jpg',
                            ],
                        ],
                    ],
                ],
            ],
        ])),
    ]);

    $handler = HandlerStack::create($mockHandler);
    $container = [];
    $handler->push(Middleware::history($container));

    $service = new Strapi(
        new Client(
            'https://example.com/graphql',
            [],
            ['handler' => $handler],
            new GuzzleClient(['handler' => $handler]),
        ),
        app('cache.store'),
    );

    $first = $service->service('strategy');
    $second = $service->service('strategy');

    expect($first)->toBeInstanceOf(ServicePageDto::class)
        ->and($first->title)->toBe('Strategy')
        ->and($first->slug)->toBe('strategy')
        ->and($first->hero->title)->toBe('Strategy Hero')
        ->and($first->logos->title)->toBe('Trusted by')
        ->and($first->logos->media)->toBe([
            'https://cdn.example.com/logo-1.jpg',
            'https://cdn.example.com/logo-2.jpg',
        ])
        ->and($first->sections)->toHaveCount(1)
        ->and($first->sections->first()->features?->first()->title)->toBe('Feature title')
        ->and($first->seo->metaTitle)->toBe('Strategy SEO')
        ->and($first->publishedAt->toIso8601String())->toBe('2026-01-10T08:00:00+00:00')
        ->and($second)->toEqual($first)
        ->and($container)->toHaveCount(1);
});

it('aborts when a service cannot be found', function () {
    $mockHandler = new MockHandler([
        new Response(200, [], json_encode([
            'data' => [
                'services' => [],
            ],
        ])),
    ]);

    $service = new Strapi(
        new Client(
            'https://example.com/graphql',
            [],
            ['handler' => HandlerStack::create($mockHandler)],
            new GuzzleClient(['handler' => HandlerStack::create($mockHandler)]),
        ),
        app('cache.store'),
    );

    expect(fn () => $service->service('missing'))
        ->toThrow(HttpException::class);
});

it('clears page cache keys and flushes tagged cache', function () {
    $taggedCache = Mockery::mock();
    $taggedCache->shouldReceive('flush')->once();

    $cache = Mockery::mock(Repository::class);
    $cache->shouldReceive('forget')->once()->with('page_home');
    $cache->shouldReceive('forget')->once()->with('page_welcome');
    $cache->shouldReceive('forget')->once()->with('seo_home');
    $cache->shouldReceive('tags')->once()->with(['page'])->andReturn($taggedCache);

    $service = new Strapi(
        new Client('https://example.com/graphql'),
        $cache,
    );

    $service->clearModel('page', 'home', 'welcome');

    expect(true)->toBeTrue();
});

it('clears the services list cache key when no identifiers are provided', function () {
    $taggedCache = Mockery::mock();
    $taggedCache->shouldReceive('flush')->once();

    $cache = Mockery::mock(Repository::class);
    $cache->shouldReceive('forget')->once()->with('services');
    $cache->shouldReceive('tags')->once()->with(['service'])->andReturn($taggedCache);

    $service = new Strapi(
        new Client('https://example.com/graphql'),
        $cache,
    );

    $service->clearModel('service', null, null);

    expect(true)->toBeTrue();
});

it('clears singleton cache keys when no identifiers are provided', function () {
    $taggedCache = Mockery::mock();
    $taggedCache->shouldReceive('flush')->once();

    $cache = Mockery::mock(Repository::class);
    $cache->shouldReceive('forget')->once()->with('social_');
    $cache->shouldReceive('tags')->once()->with(['social'])->andReturn($taggedCache);

    $service = new Strapi(
        new Client('https://example.com/graphql'),
        $cache,
    );

    $service->clearModel('social', null, null);

    expect(true)->toBeTrue();
});

it('normalizes services webhook models before clearing cache', function () {
    $taggedCache = Mockery::mock();
    $taggedCache->shouldReceive('flush')->once();

    $cache = Mockery::mock(Repository::class);
    $cache->shouldReceive('forget')->once()->with('service_strategy');
    $cache->shouldReceive('tags')->once()->with(['service'])->andReturn($taggedCache);

    $service = new Strapi(
        new Client('https://example.com/graphql'),
        $cache,
    );

    $service->clearModel('services', null, 'strategy');

    expect(true)->toBeTrue();
});

it('clears cache through the webhook endpoint', function () {
    config()->set('strapi.graphql_url', 'https://example.com/graphql');
    config()->set('strapi.webhook_secret', 'secret');

    StrapiFacade::shouldReceive('clearModel')
        ->once()
        ->with('page', 'home', 'welcome');

    $response = $this
        ->withHeaders([
            'Signature' => 'secret',
        ])
        ->postJson('/strapi/webhook', [
            'model' => 'page',
            'entry' => [
                'documentId' => 'home',
                'slug' => 'welcome',
            ],
        ]);

    $response->assertOk();

    expect($response->json())->toBe('Cache cleared');
});

it('rejects webhook requests with an invalid signature', function () {
    config()->set('strapi.graphql_url', 'https://example.com/graphql');
    config()->set('strapi.webhook_secret', 'secret');

    StrapiFacade::shouldReceive('clearModel')->never();

    $response = $this
        ->withHeaders([
            'Signature' => 'wrong-secret',
        ])
        ->postJson('/strapi/webhook', [
            'model' => 'page',
        ]);

    $response->assertStatus(400);

    expect($response->json())->toBe('Invalid webhook request.');
});

it('throws a clear exception when the webhook secret is missing', function () {
    config()->set('strapi.graphql_url', 'https://example.com/graphql');
    config()->set('strapi.webhook_secret', '');

    StrapiFacade::shouldReceive('clearModel')->never();

    $this->withoutExceptionHandling();

    expect(fn () => $this
        ->withHeaders([
            'Signature' => 'secret',
        ])
        ->postJson('/strapi/webhook', [
            'model' => 'page',
        ]))
        ->toThrow(RuntimeException::class, 'The Strapi webhook secret is not set. Make sure that the `webhook_secret` config key is configured.');
});
