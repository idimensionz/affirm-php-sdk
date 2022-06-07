<?php
declare(strict_types=1);

namespace iDimensionz\Affirm\Api;

use GuzzleHttp\Exception\GuzzleException;
use stdClass;
use const Shape\bool;
use const Shape\int;
use const Shape\string;
use function Shape\shape;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\ClientInterface as HttpClientInterface;
use GuzzleHttp\Exception\BadResponseException;

/**
 * Interact with the Affirm Transaction API.
 */
class Client
{
    /** @var string */
    const LIVE_URL = 'https://api.affirm.com/api/v1/';
    /** @var string */
    const SANDBOX_URL = 'https://sandbox.affirm.com/api/v1/';

    /** @var HttpClientInterface */
    protected $httpClient;
    /** @var string */
    protected $publicApiKey;
    /** @var string */
    protected $privateApiKey;
    /** @var bool */
    protected $isSandbox;

    /**
     * Dependency injection.
     *
     * @param string[]|bool[] $config
     * @param HttpClientInterface|null $httpClient
     */
    public function __construct(array $config, HttpClientInterface $httpClient = null)
    {
        // validate config shape (will throw TypeError if missing or wrong type)
        shape([
            'public_api_key' => string,
            'private_api_key' => string,
            'is_sandbox' => bool,
        ])($config);
        $this->publicApiKey = $config['public_api_key'];
        $this->privateApiKey = $config['private_api_key'];
        $this->isSandbox = $config['is_sandbox'];
        $this->httpClient = $httpClient ?: new HttpClient();
    }

    protected function getBaseUrl(): string
    {
        return $this->isSandbox ? self::SANDBOX_URL : self::LIVE_URL;
    }

    /**
     * Returns a list of charge or lease type transactions. Lists all transactions by default.
     * @see https://docs.affirm.com/developers/reference/list_transactions
     *
     * @return stdClass
     */
    public function list()
    {
        // @todo Implement the params to limit results.
        return $this->request(
            'POST',
            $this->getBaseUrl() . 'transactions'
        );
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
            'POST',
            $this->getBaseUrl() . 'transactions/',
            $postData
        );
    }

    /**
     * Capture a payment amount that has been initiated and authorized.
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
        $url = $this->getBaseUrl() . "transactions/$transactionId/capture";
        return $this->request('POST', $url, $optionalData);
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
        return $this->request('GET', $this->getBaseUrl() . "transactions/$transactionId$queryString");
    }

    /**
     * If optional data is included, ensure it is of the right type. Will throw
     * a TypeError if invalid type.
     *
     * @return void
     */
    public function validateOptionalData(array $optionalShape, array $optionalData)
    {
        $shape = [];
        foreach ($optionalShape as $key => $value) {
            if (array_key_exists($key, $optionalData)) {
                $shape[$key] = $value;
            }
        }
        shape($shape)($optionalData);
    }

    /**
     * Void a charge.
     *
     * @see https://docs.affirm.com/developers/reference/void_transaction
     *
     * @param string $transactionId
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

        return $this->request('POST', $this->getBaseUrl() . "transactions/$transactionId/void", $optionalData);
    }

    /**
     * Refund a charge or part of it.
     *
     * @param string $transactionId
     * @param int[] $optionalData In this case it's just `amount` that is
     * optional. Kept as an associate array for consistency with other public
     * methods.
     *
     * @return stdClass the decoded json from the response.
     * @throws GuzzleException
     */
    public function refund(string $transactionId, array $optionalData): stdClass
    {
        // if optional data is passed, it must be of this type
        // Note: metadata param is available but not implemented here yet.
        $this->validateOptionalData(['amount' => int, 'reference_id' => string, 'transaction_event_count' => int], $optionalData);
        // only include params available to send for this request
        $paramNames = ['amount', 'reference_id', 'transaction_event_count'];
        $optionalData = $this->whitelistArray($optionalData, $paramNames);

        return $this->request(
            'POST',
            $this->getBaseUrl() . "transactions/$transactionId/refund",
            $optionalData
        );
    }

    /**
     * Internal method to send a request to affirm and get a response.
     *
     * @param string $httpVerb
     * @param string $url
     * @param array $postData
     * @return stdClass the decoded json from the response
     * @throws GuzzleException
     */
    protected function request(
        string $httpVerb,
        string $url,
        array $postData = []
    ): stdClass {
        try {
            // request via guzzle
            $requestData = [
                'auth' => [
                    $this->publicApiKey,
                    $this->privateApiKey,
                ],
            ];
            if ($postData) {
                $requestData['json'] = $postData;
            }
            $response = $this->httpClient->request(
                $httpVerb,
                $url,
                $requestData
            );
        } catch (BadResponseException $exception) {
            // if guzzle fails, rethrow affirm exception
            throw new ResponseException(
                $exception->getMessage(),
                $exception->getCode(),
                $exception
            );
        }
        $responseBody = $response->getBody()->getContents();
        $responseData = json_decode($responseBody);
        if (json_last_error() !== JSON_ERROR_NONE) {
            // if json couldn't be decoded, throw exception
            $message = "Json could not be decoded from affirm response. " .
                "Response body: $responseBody";
            throw new ResponseException($message);
        }
        return $responseData;
    }

    /**
     * Get only the elements in an array whose keys match a whitelist of keys
     *
     * @param array $array
     * @param int[]|string[] $whitelistedKeys
     *
     * @return array
     */
    protected function whitelistArray(array $array, array $whitelistedKeys): array
    {
        return array_filter(
            array_intersect_key(
                $array,
                array_flip($whitelistedKeys)
            )
        );
    }
}
