<?php

namespace iDimensionz\Affirm\Api;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\ClientInterface as HttpClientInterface;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use const Shape\bool;
use const Shape\string;
use function Shape\shape;
use stdClass;

abstract class AbstractAffirmClient
{
    public const HTTP_METHOD_POST = 'POST';
    public const HTTP_METHOD_GET = 'GET';

    /** @var HttpClientInterface */
    protected $httpClient;
    /** @var string */
    protected $publicApiKey;
    /** @var bool */
    protected $isSandbox;
    /** @var string */
    protected $privateApiKey;

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
        ])(
            $config
        );
        $this->publicApiKey = $config['public_api_key'];
        $this->privateApiKey = $config['private_api_key'];
        $this->isSandbox = $config['is_sandbox'];
        $this->httpClient = $httpClient ?: new HttpClient();
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

    protected function getBaseUrl(): string
    {
        return $this->isSandbox ? static::SANDBOX_URL : static::LIVE_URL;
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
            $message = "Json could not be decoded from Affirm response. " .
                "Response body: $responseBody";
            throw new ResponseException($message);
        }
        return $responseData;
    }
}
