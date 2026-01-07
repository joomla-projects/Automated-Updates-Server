<?php

declare(strict_types=1);

namespace App\RemoteSite;

use App\Enum\HttpMethod;
use App\RemoteSite\Responses\FinalizeUpdate as FinalizeUpdateResponse;
use App\RemoteSite\Responses\GetUpdate as GetUpdateResponse;
use App\RemoteSite\Responses\HealthCheck as HealthCheckResponse;
use App\RemoteSite\Responses\PrepareUpdate as PrepareUpdateResponse;
use App\RemoteSite\Responses\Notification as NotificationResponse;
use App\RemoteSite\Responses\ResponseInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Support\Facades\App;
use Psr\Http\Message\RequestInterface;

/**
 * @method  HealthCheckResponse checkHealth()
 * @method  GetUpdateResponse getUpdate()
 * @method  PrepareUpdateResponse prepareUpdate(array<string,string> $data)
 * @method  FinalizeUpdateResponse finalizeUpdate(array<string,string|null> $data)
 * @method  NotificationResponse notificationSuccess(array<string,string> $data)
 * @method  NotificationResponse notificationFailed(array<string,string> $data)
 */
class Connection
{
    public function __construct(protected readonly string $baseUrl, protected readonly string $key)
    {
    }

    public function __call(string $method, array $arguments): ResponseInterface
    {
        $endpoint = WebserviceEndpoint::tryFromName($method);

        if (is_null($endpoint)) {
            throw new \BadMethodCallException();
        }

        // Call
        $data = $this->performWebserviceRequest(
            $endpoint->getMethod(),
            $endpoint->getUrl(),
            ...$arguments
        );

        $responseClass = $endpoint->getResponseClass();

        return $responseClass::from($data);
    }

    public function performExtractionRequest(array $requestData): array
    {
        $request = new Request(
            'POST',
            $this->baseUrl . '/index.php?jautoupdate=1'
        );

        $requestData['password'] = $this->key;

        // Get result
        $response = $this->performHttpRequest(
            $request,
            [
                'form_params' => $requestData,
                'timeout' => 300.0
            ]
        );

        return $this->decodeResponse($response, $request);
    }

    public function performWebserviceRequest(
        HttpMethod $method,
        string $endpoint,
        ?array $requestData = null
    ): array {
        $request = new Request(
            $method->name,
            $this->baseUrl . $endpoint,
            [
                'X-JUpdate-Token' => $this->key
            ]
        );

        // Get result
        $response = $this->performHttpRequest(
            $request,
            [
                "json" => $requestData,
                "headers" => [
                    "Content-Type" => "application/json",
                    "Accept" => "application/vnd.api+json"
                ],
                'timeout' => 60.0,
                'connect_timeout' => 5.0,
            ]
        );

        $responseData = $this->decodeResponse($response, $request);

        // Make sure it matches the Joomla webservice response format
        if (empty($responseData['data']['attributes'])) {
            throw new RequestException(
                "Invalid JSON format",
                $request,
                $response
            );
        }

        return $responseData['data']['attributes'];
    }

    protected function performHttpRequest(
        RequestInterface $request,
        array $options = []
    ): Response {
        /** @var Client $httpClient */
        $httpClient = App::make(Client::class);

        // Send a streamed response to be able to validate the size
        $options['stream'] = true;
        $options['progress'] = function (
            $downloadTotal,
            $downloadedBytes
        ) use ($request) {
            if ($downloadedBytes > 1024000) {
                throw new \RuntimeException("Implausible response size while fetching from " . $request->getUri());
            }
        };

        /** @var Response $response */
        $response = $httpClient->send(
            $request,
            $options
        );

        // Convert the streamed response into a "normal" one
        $buffer = '';

        while (!$response->getBody()->eof()) {
            $buffer .= $response->getBody()->read(8192);
        }

        // Overwrite streamed body
        $response = $response->withBody(Utils::streamFor($buffer));

        // Validate response
        if (!json_validate((string) $response->getBody())) {
            throw new RequestException(
                "Invalid JSON body",
                $request,
                $response
            );
        }

        return $response;
    }

    protected function decodeResponse(Response $response, Request $request): array
    {
        // Decode
        $data = json_decode(
            (string) $response->getBody(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        // Make sure it's an array
        if (!is_array($data)) {
            throw new RequestException(
                "Invalid JSON body",
                $request,
                $response
            );
        }

        return $data;
    }
}
