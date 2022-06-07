<?php
declare(strict_types = 1);

namespace iDimensionz\Affirm\Api;

use GuzzleHttp\Exception\GuzzleException;
use stdClass;

use const Shape\int;
use const Shape\string;

class ChargesApiClient extends AbstractAffirmClient
{
    /** @var string */
    const LIVE_URL = 'https://api.affirm.com/api/v2/';
    /** @var string */
    const SANDBOX_URL = 'https://sandbox.affirm.com/api/v2/';

    /**
     * Authorize a payment that has been initiated.
     *
     * @param string $checkoutToken
     * @param string[] $optionalData In this case it's just `order_id`
     * that is optional. Kept as an associate array for consistency with other
     * public methods.
     *
     * @return stdClass the decoded json from the response
     * @throws GuzzleException
     */
    public function authorize(
        string $checkoutToken,
        array $optionalData = []
    ): stdClass {
        $postData = ['checkout_token' => $checkoutToken];
        // ensure it's the correct type if it's set
        $this->validateOptionalData([
            'order_id' => string,
        ], $optionalData);
        // get only the available optional data
        $paramNames = ['order_id'];
        $optionalData = $this->whitelistArray($optionalData, $paramNames);
        // add to the post data and send
        $postData = array_merge($postData, $optionalData);
        return $this->request(
            self::HTTP_METHOD_POST,
            $this->getBaseUrl() . '/',
            $postData
        );
    }

    /**
     * Capture a payment amount that has been initiated and authorized.
     *
     * @param string $chargeId
     * @param string[] $optionalData
     *
     * @return stdClass the decoded json from the response
     * @throws GuzzleException
     */
    public function capture(string $chargeId, array $optionalData = []): stdClass
    {
        $this->validateOptionalData([
            'order_id' => string,
            'shipping_carrier' => string,
            'shipping_confirmation' => string,
        ], $optionalData);
        $paramNames = ['order_id', 'shipping_carrier', 'shipping_confirmation'];
        $optionalData = $this->whitelistArray($optionalData, $paramNames);
        $url = $this->getBaseUrl() . "/$chargeId/capture";
        return $this->request(self::HTTP_METHOD_POST, $url, $optionalData);
    }

    /**
     * Get details of a charge.
     *
     * @param string $chargeId
     * @param array $optionalData
     * @return stdClass the decoded json from the response.
     * @throws GuzzleException
     */
    public function read(string $chargeId, array $optionalData = []): stdClass
    {
        // if optional data is passed, it must be of this type
        $this->validateOptionalData([
            'limit' => int,
            'before' => string,
            'after' => string,
        ], $optionalData);
        // only include params available to send for this request
        $paramNames = ['limit', 'before', 'after'];
        $optionalData = $this->whitelistArray($optionalData, $paramNames);
        $queryString = $optionalData ? '?' . http_build_query($optionalData) : '';
        return $this->request(self::HTTP_METHOD_GET, $this->getBaseUrl() . "/$chargeId$queryString");
    }

    /**
     * Void a charge.
     *
     * @param string $chargeId
     * @return stdClass the decoded json from the response.
     * @throws GuzzleException
     */
    public function void(string $chargeId): stdClass
    {
        return $this->request(self::HTTP_METHOD_POST, $this->getBaseUrl() . "/$chargeId/void");
    }

    /**
     * Refund a charge or part of it.
     *
     * @param string $chargeId
     * @param int[] $optionalData In this case it's just `amount` that is
     * optional. Kept as an associate array for consistency with other public
     * methods.
     *
     * @return stdClass the decoded json from the response.
     * @throws GuzzleException
     */
    public function refund(string $chargeId, array $optionalData): stdClass
    {
        // if optional data is passed, it must be of this type
        $this->validateOptionalData(['amount' => int], $optionalData);
        // only include params available to send for this request
        $paramNames = ['amount'];
        $optionalData = $this->whitelistArray($optionalData, $paramNames);

        return $this->request(
            self::HTTP_METHOD_POST,
            $this->getBaseUrl() . "/$chargeId/refund",
            $optionalData
        );
    }

    protected function getBaseUrl(): string
    {
        return parent::getBaseUrl() . 'charges';
    }
}
