# This is my package laravel-strapi

[![Latest Version on Packagist](https://img.shields.io/packagist/v/combindma/laravel-strapi.svg?style=flat-square)](https://packagist.org/packages/combindma/laravel-strapi)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/combindma/laravel-strapi/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/combindma/laravel-strapi/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/combindma/laravel-strapi/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/combindma/laravel-strapi/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/combindma/laravel-strapi.svg?style=flat-square)](https://packagist.org/packages/combindma/laravel-strapi)

This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.

## About Combind Agency

[Combine Agency](https://combind.ma?utm_source=github&utm_medium=banner&utm_campaign=package_name) is a leading web development agency specializing in building innovative and high-performance web applications using modern technologies. Our experienced team of developers, designers, and project managers is dedicated to providing top-notch services tailored to the unique needs of our clients.

If you need assistance with your next project or would like to discuss a custom solution, please feel free to [contact us](mailto:hello@combind.ma) or visit our [website](https://combind.ma?utm_source=github&utm_medium=banner&utm_campaign=package_name) for more information about our services. Let's build something amazing together!


## Installation

You can install the package via composer:

```bash
composer require combindma/laravel-strapi
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="laravel-strapi-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="laravel-strapi-config"
```

This is the contents of the published config file:

```php
return [
];
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="laravel-strapi-views"
```

## Usage

```php
$variable = new Combindma\Strapi();
echo $variable->echoPhrase('Hello, Combindma!');
```

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
