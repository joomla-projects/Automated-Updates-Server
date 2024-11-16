<?php

declare(strict_types=1);

namespace App\Services;

use App\Enum\HttpMethod;
use App\Enum\WebserviceEndpoint;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\App;
use Psr\Http\Message\RequestInterface;

class SiteConnectionService
{
    public function __construct(protected readonly string $baseUrl, protected readonly string $key)
    {
    }

    public function checkHealth(): array
    {
        $healthData = $this->performWebserviceRequest(
            HttpMethod::GET,
            WebserviceEndpoint::HEALTH_CHECK
        );

        // Perform a sanity check
        if (empty($healthData['cms_version'])) {
            throw new \Exception("Invalid health response content");
        }

        return $healthData;
    }

    public function performExtractionRequest(array $data): array
    {
        $request = new Request(
            'POST',
            $this->baseUrl . 'extract.php'
        );

        $data['password'] = $this->key;

        return $this->performHttpRequest(
            $request,
            [
                'form_params' => $data,
                'timeout' => 300.0
            ]
        );
    }

    protected function performWebserviceRequest(
        HttpMethod $method,
        WebserviceEndpoint $endpoint,
        array $data = []
    ): array {
        $request = new Request(
            $method->name,
            $this->baseUrl . $endpoint->value,
            [
                'Authorization' => 'JUpdate-Token ' . $this->key
            ]
        );

        return $this->performHttpRequest(
            $request,
            [
                "json" => $data
            ]
        );
    }

    protected function performHttpRequest(
        RequestInterface $request,
        array $options = []
    ): array {
        /** @var Client $httpClient */
        $httpClient = App::make(Client::class);

        /** @var Response $response */
        $response = $httpClient->send(
            $request,
            $options
        );

        // Validate response
        if (!json_validate((string) $response->getBody())) {
            throw new RequestException(
                "Invalid JSON body",
                $request,
                $response
            );
        }

        // Return decoded body
        return json_decode(
            (string) $response->getBody(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    }
}
