# HubSpot Provider for OAuth 2.0 Client

[![Latest Version](https://img.shields.io/github/release/helpscout/oauth2-hubspot.svg?style=flat-square)](https://github.com/helpscout/oauth2-hubspot/releases)
[![Software license](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](https://raw.githubusercontent.com/helpscout/oauth2-hubspot/develop/LICENSE)
[![Build Status](https://img.shields.io/travis/helpscout/oauth2-hubspot/master.svg?style=flat-square)](https://travis-ci.org/helpscout/oauth2-hubspot)

This package provides a HubSpot OAuth 2.0 support for the PHP League's [OAuth 2.0 Client](https://github.com/thephpleague/oauth2-client).

## Installation

To install, use composer:

```
composer require helpscout/oauth2-hubspot
```

## Usage

Usage is the same as The League's OAuth client, using `\HelpScout\OAuth2\Client\Provider\HubSpot` as the provider.

### Authorization Code Flow

```php
$provider = new HelpScout\OAuth2\Client\Provider\HubSpot([
    'clientId'          => '{hubspot-client-id}',
    'clientSecret'      => '{hubspot-client-secret}',
    'redirectUri'       => 'https://example.com/callback-url'
]);
```
For further usage of this package please refer to the [core package documentation on "Authorization Code Grant"](https://github.com/thephpleague/oauth2-client#usage).

### Refreshing a Token

```php
$provider = new HelpScout\OAuth2\Client\Provider\HubSpot([
    'clientId'          => '{hubspot-client-id}',
    'clientSecret'      => '{hubspot-client-secret}',
    'redirectUri'       => 'https://example.com/callback-url'
]);

$existingAccessToken = getAccessTokenFromYourDataStore();

if ($existingAccessToken->hasExpired()) {
    $newAccessToken = $provider->getAccessToken('refresh_token', [
        'refresh_token' => $existingAccessToken->getRefreshToken()
    ]);

    // Purge old access token and store new access token to your data store.
}
```

For further usage of this package please refer to the [core package documentation on "Refreshing a Token"](https://github.com/thephpleague/oauth2-client#refreshing-a-token).

## Testing

``` bash
$ ./vendor/bin/phpunit
```

## Contributing

Please see [CONTRIBUTING](https://github.com/helpscout/oauth2-hubspot/blob/master/CONTRIBUTING.md) for details.


## Credits

- [The Help Scout Platform Team](https://github.com/helpscout)
- [All Contributors](https://github.com/helpscout/oauth2-hubspot/contributors)


## License

The MIT License (MIT). Please see [License File](https://github.com/helpscout/oauth2-hubspot/blob/master/LICENSE) for more information.
