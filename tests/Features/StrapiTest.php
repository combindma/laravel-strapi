<?php

use Combindma\Strapi\DataObjects\SeoDto;
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
