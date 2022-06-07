# Unofficial Affirm PHP SDK

[Affirm Transaction API Docs](https://docs.affirm.com/developers/reference/transaction-api-endpoints)


## Install:
```sh
composer require idimensionz/affirm-php-sdk
```

## Usage:
```php
// get an affirm php sdk instance
$config = [
    'public_api_key' => 'MY_AFFIRM_PUBLIC_API_KEY',
    'private_api_key' => 'MY_AFFIRM_PRIVATE_API_KEY',
    'is_sandbox' => true,
];
$affirm = new \iDimensionz\Affirm\Api\Client($config);

// Authorize an Affirm payment by checkout token
/** @var \stdClass $response decoded json from response */
$optionalData = ['order_id' => 'OPTIONAL_ORDER_ID'];
$response = $affirm->authorize('MY_CHECKOUT_TOKEN', $optionalData);

// capture an authorized affirm payment by transaction id
$optionalData = [
    'order_id' => 'abc123',
    'shipping_carrier' => 'my carrier',
    'shipping_confirmation' => 'abc123',
];
$response = $affirm->capture('MY_TRANSACTION_ID', $optionalData);

// read the details of an authorized transaction by transaction id
$optionalData = [
    'expand' => 'checkout,events',
];
$response = $affirm->read('MY_TRANSACTION_ID', $optionalData);
```
