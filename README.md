# This is my package sparkcommerce-rest-routes

[![Latest Version on Packagist](https://img.shields.io/packagist/v/rahat1994/sparkcommerce-rest-routes.svg?style=flat-square)](https://packagist.org/packages/rahat1994/sparkcommerce-rest-routes)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/rahat1994/sparkcommerce-rest-routes/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/rahat1994/sparkcommerce-rest-routes/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/rahat1994/sparkcommerce-rest-routes/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/rahat1994/sparkcommerce-rest-routes/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/rahat1994/sparkcommerce-rest-routes.svg?style=flat-square)](https://packagist.org/packages/rahat1994/sparkcommerce-rest-routes)

This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.

## Support us

[<img src="https://github-ads.s3.eu-central-1.amazonaws.com/sparkcommerce-rest-routes.jpg?t=1" width="419px" />](https://spatie.be/github-ad-click/sparkcommerce-rest-routes)

We invest a lot of resources into creating [best in class open source packages](https://spatie.be/open-source). You can support us by [buying one of our paid products](https://spatie.be/open-source/support-us).

We highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using. You'll find our address on [our contact page](https://spatie.be/about-us). We publish all received postcards on [our virtual postcard wall](https://spatie.be/open-source/postcards).

## Requirements

```bash
1. Must Have sanctum installed.
2. Follow [page](https://laravel.com/docs/11.x/passwords) to setup forgot passwords.
```

## Installation

You can install the package via composer:

```bash
composer require rahat1994/sparkcommerce-rest-routes
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="sparkcommerce-rest-routes-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="sparkcommerce-rest-routes-config"
```

This is the contents of the published config file:

```php
return [
];
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="sparkcommerce-rest-routes-views"
```

## Usage

```php
$sparkcommerceRestRoutes = new Rahat1994\SparkcommerceRestRoutes();
echo $sparkcommerceRestRoutes->echoPhrase('Hello, Rahat1994!');
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

- [Rahat Baksh](https://github.com/rahat1994)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
