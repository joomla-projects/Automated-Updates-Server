<?php

namespace App\TUF;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ResponseException;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;
use Tuf\Exception\RepoFileNotFound;
use Tuf\Loader\LoaderInterface;

class HttpLoader implements LoaderInterface
{
    public function __construct(private readonly string $repositoryPath, private readonly Client $http)
    {
    }

    public function load(string $locator, int $maxBytes): PromiseInterface
    {
        try {
            $response = $this->http->get($this->repositoryPath . $locator);
        } catch (ResponseException $e) {
            if ($e->getResponse()->getStatusCode() !== 200) {
                throw new RepoFileNotFound();
            }

            throw new HttpLoaderException($e->getMessage(), $e->getCode(), $e);
        } catch (RequestException $e) {
            // Request failed without a response
            throw new HttpLoaderException($e->getMessage(), $e->getCode(), $e);
        }

        // Rewind to start
        $response->getBody()->rewind();

        // Return response
        return new FulfilledPromise($response->getBody());
    }
}
