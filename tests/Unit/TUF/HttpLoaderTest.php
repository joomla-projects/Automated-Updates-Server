<?php

namespace Tests\Unit\TUF;

use App\TUF\HttpLoader;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Stream;
use Tests\TestCase;
use Tuf\Exception\RepoFileNotFound;

class HttpLoaderTest extends TestCase
{
    protected const REPOPATHMOCK = 'https://example.org/tuftest/';

    protected HttpLoader $object;

    public function testLoaderQueriesCorrectUrl()
    {
        $responseBody = $this->createMock(Stream::class);

        $object = new HttpLoader(
            self::REPOPATHMOCK,
            $this->getHttpClientMock(200, $responseBody, 'root.json')
        );

        $object->load('root.json', 2048);
    }

    public function testLoaderForwardsReturnedBodyFromHttpClient()
    {
        $responseBody = $this->createMock(Stream::class);

        $object = new HttpLoader(
            self::REPOPATHMOCK,
            $this->getHttpClientMock(200, $responseBody, 'root.json')
        );

        $this->assertSame(
            $responseBody,
            $object->load('root.json', 2048)->wait()
        );
    }

    public function testLoaderThrowsExceptionForNon200Response()
    {
        $this->expectException(RepoFileNotFound::class);

        $responseBody = $this->createMock(Stream::class);

        $object = new HttpLoader(
            self::REPOPATHMOCK,
            $this->getHttpClientMock(400, $responseBody, 'root.json')
        );

        $object->load('root.json', 2048);
    }

    protected function getHttpClientMock(int $responseCode, Stream $responseBody, string $expectedFile)
    {
        $responseMock = $this->createMock(Response::class);
        $responseMock->method('getBody')->willReturn($responseBody);

        $httpClientMock = $this->createMock(Client::class);

        if ($responseCode !== 200) {
            $httpClientMock->expects($this->once())
                ->method('get')
                ->with(self::REPOPATHMOCK . $expectedFile)
                ->willThrowException(new RequestException(
                    "Request Exception",
                    new Request('GET', self::REPOPATHMOCK . $expectedFile),
                    new Response($responseCode)
                ));
        } else {
            $httpClientMock->expects($this->once())
                ->method('get')
                ->with(self::REPOPATHMOCK . $expectedFile)
                ->willReturn($responseMock);
        }

        return $httpClientMock;
    }
}
