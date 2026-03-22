# Laravel Strapi

[![Latest Version on Packagist](https://img.shields.io/packagist/v/combindma/laravel-strapi.svg?style=flat-square)](https://packagist.org/packages/combindma/laravel-strapi)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/combindma/laravel-strapi/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/combindma/laravel-strapi/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/combindma/laravel-strapi/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/combindma/laravel-strapi/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/combindma/laravel-strapi.svg?style=flat-square)](https://packagist.org/packages/combindma/laravel-strapi)

`combindma/laravel-strapi` is a Laravel package for consuming a Strapi v5 GraphQL API through a single service class. It ships with a configured GraphQL client, a Laravel facade, built-in caching, and DTOs for common content shapes such as SEO, pages, services, social links, privacy content, and contact details.

Instead of scattering GraphQL requests across controllers and views, the package centralizes Strapi access behind a small Laravel-friendly API. You can use its ready-made methods for structured content or drop down to the low-level `query()` method when you need to run a custom GraphQL query.

## About Combind Agency

[Combine Agency](https://combind.ma?utm_source=github&utm_medium=banner&utm_campaign=package_name) is a leading web development agency specializing in building innovative and high-performance web applications using modern technologies. Our experienced team of developers, designers, and project managers is dedicated to providing top-notch services tailored to the unique needs of our clients.

If you need assistance with your next project or would like to discuss a custom solution, please feel free to [contact us](mailto:hello@combind.ma) or visit our [website](https://combind.ma?utm_source=github&utm_medium=banner&utm_campaign=package_name) for more information about our services. Let's build something amazing together!


## Installation

You can install the package via composer:

```bash
composer require combindma/laravel-strapi
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="laravel-strapi-config"
```

This is the contents of the published config file:

```php
return [
    'graphql_url' => env('STRAPI_GRAPHQL_URL'),
    'token' => env('STRAPI_TOKEN'),
    'timeout' => (int) env('STRAPI_TIMEOUT', 30),
    'webhook_secret' => env('STRAPI_WEBHOOK_SECRET', ''),
];
```

Example `.env` values:

```env
STRAPI_GRAPHQL_URL=https://your-strapi-domain.com/graphql
STRAPI_TOKEN=your-strapi-api-token
STRAPI_TIMEOUT=30
STRAPI_WEBHOOK_SECRET=your-strapi-webhook-secret
```

## Usage

The package registers `Combindma\Strapi\Strapi` as a singleton and exposes it through the `Strapi` facade.

### Resolve the service

```php
use Combindma\Strapi\Strapi;

$strapi = app(Strapi::class);
```

Or use the facade:

```php
use Combindma\Strapi\Facades\Strapi;

$seo = Strapi::seo('home');
```

### Available methods

The `Strapi` class currently provides these higher-level methods:

- `seo(string $documentId): SeoDto`
- `page(string $documentId): PageDto`
- `socialNetworks(): SocialDto`
- `privacy(): ?string`
- `contactInfos(): ContactDto`
- `services(): Illuminate\Support\Collection`
- `service(string $slug): ServicePageDto`
- `clearModel(string $model, ?string $documentId, ?string $slug): void`
- `query(GraphQL\Query $query, bool $resultsAsArray = false, array $variables = []): object|array`

All content methods are cached with Laravel's cache store, so repeated calls do not hit Strapi again until you clear the relevant cache entries.

### Example: fetch SEO and page content

```php
use Combindma\Strapi\Facades\Strapi;

$seo = Strapi::seo('home');
$page = Strapi::page('home');

$title = $seo->metaTitle;
$heroTitle = $page->hero?->title;
$sections = $page->sections;
```

### Example: fetch the services listing

```php
use Combindma\Strapi\Facades\Strapi;

$services = Strapi::services();

foreach ($services as $service) {
    echo $service->title;
    echo $service->slug;
    echo $service->image->url;
}
```

### Example: fetch a service details page

```php
use Combindma\Strapi\Facades\Strapi;

$service = Strapi::service('strategy');

$hero = $service->hero;
$logos = $service->logos->media;
$seo = $service->seo;
```

If the requested service does not exist, `service()` aborts with a `404`.

### Example: clear cached content manually

```php
use Combindma\Strapi\Facades\Strapi;

Strapi::clearModel('page', 'home', 'welcome');
Strapi::clearModel('service', null, 'strategy');
Strapi::clearModel('social', null, null);
```

This clears the relevant cache keys for the given model and also flushes the tagged cache for that model.

### Example: run a custom GraphQL query

```php
use Combindma\Strapi\Facades\Strapi;
use GraphQL\Query;
use GraphQL\RawObject;

$query = (new Query('page'))
    ->setArguments([
        'documentId' => new RawObject('"home"'),
    ])
    ->setSelectionSet([
        'title',
    ]);

$data = Strapi::query($query);

$title = $data->page->title;
```

### Returned data objects

The package maps Strapi responses into readonly DTOs so your application code can work with typed objects instead of raw arrays. Responsive images are normalized into `ResponsiveImageDto`, including a generated `srcset` string when Strapi image formats are available.

## Cache and Webhooks

This package stores Strapi content in Laravel cache forever after the first request. For example, the first time your application calls `Strapi::page('home')`, the content is fetched from Strapi and then saved in cache. Every later call reads from cache until that content is cleared.

When a user updates content in Strapi CMS, you should trigger cache invalidation through the built-in webhook endpoint so Laravel can forget the stale content and fetch the fresh version on the next request.

### 1. Configure the webhook secret

Add the webhook secret to your Laravel environment:

```env
STRAPI_WEBHOOK_SECRET=your-strapi-webhook-secret
```

The same secret must also be used in your Strapi webhook configuration.

### 2. Exclude the webhook route from CSRF protection

In `bootstrap/app.php`, add the exception inside the `withMiddleware()` callback:

```php
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: ['/strapi/webhook']);
    })
    ->create();
```

### 3. Configure the webhook in Strapi

In Strapi, create a webhook that sends requests when content is updated.

Use this Laravel endpoint:

```text
https://domain.com/strapi/webhook
```

Use the same webhook secret you configured in Laravel.

In the Strapi webhook headers, add:

```text
Signature: your-strapi-webhook-secret
```

The package validates the `Signature` header against `STRAPI_WEBHOOK_SECRET` before clearing cache.

### 4. What happens when Strapi sends the webhook

When Strapi sends a valid webhook request, the package webhook controller calls `clearModel()` and removes the relevant cached content.

Examples:

- Updating a page can clear `page_{documentId}` and `seo_{documentId}`.
- Updating a service can clear `service_{slug}` and the tagged `service` cache.
- Updating singleton content such as social links, privacy content, or contact info can clear their single cached keys.

After that, the next request to Laravel loads the updated content from Strapi and stores it in cache again.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Combind](https://github.com/combindma)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
