<?php

namespace App\TUF;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\Create;
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
        } catch (RequestException $e) {
            if ($e->getResponse()?->getStatusCode() !== 200) {
                throw new RepoFileNotFound();
            }

            throw new HttpLoaderException($e->getMessage(), $e->getCode(), $e);
        }

        // Rewind to start
        $response->getBody()->rewind();

        // Return response
        return Create::promiseFor($response->getBody());
    }
}
