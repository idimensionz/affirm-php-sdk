<?php

/** @noinspection PhpMultipleClassDeclarationsInspection */
declare(strict_types=1);

namespace iDimensionz\Affirm\Api;

use GuzzleHttp\Exception\GuzzleException;
use const Shape\int;
use const Shape\string;
use stdClass;

/**
 * Interact with the Affirm Transaction API.
 */
class TransactionApiClient extends AbstractAffirmClient
{
    /** @var string */
    public const SANDBOX_URL = 'https://sandbox.affirm.com/api/v1/transactions';
    /** @var string */
    public const LIVE_URL = 'https://api.affirm.com/api/v1/transactions';

    /**
     * Returns a list of charge or lease type transactions. Lists all transactions by default.
     * @see https://docs.affirm.com/developers/reference/list_transactions
     *
     * @return stdClass
     * @throws GuzzleException
     */
    public function list(): stdClass
    {
        // @todo Implement the params to limit results.
        return $this->request(self::HTTP_METHOD_GET, $this->getBaseUrl());
    }

    /**
     * Authorizes a transaction.
     * @see https://docs.affirm.com/developers/reference/authorize_transaction
     *
     * @throws ResponseException|GuzzleException if something goes wrong
     *
     * @param string[] $optionalData In this case it's just `order_id`
     * that is optional. Kept as an associate array for consistency with other
     * public methods.
     *
     * @return stdClass the decoded json from the response
     */
    public function authorize(string $checkoutToken, array $optionalData = []): stdClass {
        $postData = ['checkout_token' => $checkoutToken];
        // ensure it's the correct type if it's set
        $this->validateOptionalData(['order_id' => string,], $optionalData);
        // get only the available optional data
        $paramNames = ['order_id'];
        $optionalData = $this->whitelistArray($optionalData, $paramNames);
        // add to the post data and send
        $postData = array_merge($postData, $optionalData);

        return $this->request(self::HTTP_METHOD_POST, $this->getBaseUrl(), $postData);
    }

    /**
     * Returns a list of transaction events.
     * @see https://docs.affirm.com/developers/reference/list_transaction_events
     *
     * @param array $optionalData
     * @return stdClass
     * @throws GuzzleException
     */
    public function listEvents(array $optionalData): stdClass
    {
        // @todo Implement the rest of the params to limit results.
        $this->validateOptionalData([
            'transaction_type' => string,
            'transaction_event_type' => string,
            'limit' => string,
            'before_date' => string,
            'after_date' => string,
            'before_ari' => string,
            'after_ari' => string,
            'merchant_id' => string,
        ], $optionalData);
        $paramNames = [
            'transaction_type',
            'transaction_event_type',
            'limit',
            'before_date',
            'after_date',
            'before_ari',
            'after_ari',
            'merchant_id'
        ];
        $optionalData = $this->whitelistArray($optionalData, $paramNames);

        return $this->request(
            self::HTTP_METHOD_GET,
            $this->getBaseUrl() . '/events',
            $optionalData
        );
    }

    /**
     * Get details of a charge.
     *
     * @see https://docs.affirm.com/developers/reference/read_transaction
     *
     * @param string[]|int[] $optionalData can include `limit`, `before`, `after`
     *
     * @return stdClass the decoded json from the response.
     * @throws GuzzleException
     */
    public function read(string $transactionId, array $optionalData = []): stdClass
    {
        // if optional data is passed, it must be of this type
        $this->validateOptionalData([
            'expand' => string
        ], $optionalData);
        // only include params available to send for this request
        $paramNames = ['expand'];
        $optionalData = $this->whitelistArray($optionalData, $paramNames);
        $queryString = $optionalData ? '?' . http_build_query($optionalData) : '';
        return $this->request(self::HTTP_METHOD_GET, $this->getBaseUrl() . "/$transactionId$queryString");
    }

    /**
     * Updates the specified transaction by setting the values of the parameters passed.
     * Any parameters not provided will be left unchanged.
     * @see https://docs.affirm.com/developers/reference/update_transaction
     *
     * @param string $transactionId
     * @param array $optionalData
     * @return stdClass
     * @throws GuzzleException
     */
    public function update(string $transactionId, array $optionalData): stdClass
    {
        $this->validateOptionalData([
            'order_id' => string,
            'reference_id' => string,
            'shipping_carrier' => string,
            'shipping_confirmation' => string,
            // shipping : object not included. @todo Implement this parameter.
        ], $optionalData);
        $paramNames = ['order_id', 'reference_id', 'shipping_carrier', 'shipping_confirmation'];
        $optionalData = $this->whitelistArray($optionalData, $paramNames);
        $url = $this->getBaseUrl() . "/$transactionId";
        return $this->request(self::HTTP_METHOD_POST, $url, $optionalData);
    }

    /**
     * Capture a payment amount that has been initiated and authorized.
     * @see https://docs.affirm.com/developers/reference/capture_transaction
     *
     * @param string $transactionId
     * @param string[] $optionalData
     *
     * @return stdClass the decoded json from the response
     * @throws GuzzleException
     */
    public function capture(string $transactionId, array $optionalData = []): stdClass
    {
        $this->validateOptionalData([
            'order_id' => string,
            'shipping_carrier' => string,
            'shipping_confirmation' => string,
        ], $optionalData);
        $paramNames = ['order_id', 'shipping_carrier', 'shipping_confirmation'];
        $optionalData = $this->whitelistArray($optionalData, $paramNames);
        $url = $this->getBaseUrl() . "/$transactionId/capture";
        return $this->request(self::HTTP_METHOD_POST, $url, $optionalData);
    }

    /**
     * Refund a transaction that was previously created but that hasn't been refunded yet.
     * @see https://docs.affirm.com/developers/reference/refund_transaction
     *
     * @param string $transactionId
     * @param array $optionalData
     * @return stdClass
     * @throws GuzzleException
     */
    public function refund(string $transactionId, array $optionalData): stdClass
    {
        $this->validateOptionalData([
            'amount' => int,
            'reference_id' => string,
            'transaction_event_count' => int,
            // metadata : not implemented yet because its an "object". @todo Implement this parameter.
        ], $optionalData);
        $paramNames = ['amount', 'reference_id', 'transaction_event_count'];
        $optionalData = $this->whitelistArray($optionalData, $paramNames);
        $url = $this->getBaseUrl() . "/$transactionId/refund";

        return $this->request(self::HTTP_METHOD_POST, $url, $optionalData);
    }

    /**
     * Void a charge.
     * @see https://docs.affirm.com/developers/reference/void_transaction
     *
     * @param string $transactionId
     * @param array|null $optionalData
     * @return stdClass the decoded json from the response.
     * @throws GuzzleException
     */
    public function void(string $transactionId, ?array $optionalData): stdClass
    {
        // if optional data is passed, it must be of this type
        // Note: metadata param is available but not implemented here yet.
        $this->validateOptionalData(['reference_id' => string, 'amount' => int], $optionalData);
        // only include params available to send for this request
        $paramNames = ['reference_id', 'amount'];
        $optionalData = $this->whitelistArray($optionalData, $paramNames);

        return $this->request(self::HTTP_METHOD_POST, $this->getBaseUrl() . "/$transactionId/void", $optionalData);
    }

    /**
     * Download daily revenue share csv file from s3.
     * @see https://docs.affirm.com/developers/reference/download_revenue_share_csv
     *
     * @param array $optionalData
     * @return stdClass
     * @throws GuzzleException
     */
    public function downloadRevenueShareCsv(array $optionalData): stdClass
    {
        // if optional data is passed, it must be of this type
        $this->validateOptionalData([
            'date' => string
        ], $optionalData);
        // only include params available to send for this request
        $paramNames = ['date'];
        $optionalData = $this->whitelistArray($optionalData, $paramNames);
        $queryString = $optionalData ? '?' . http_build_query($optionalData) : '';

        return $this->request(self::HTTP_METHOD_GET, $this->getBaseUrl() . "/download_revenue_share_csv$queryString");
    }
}
