<?php declare(strict_types=1);

namespace App\Models;

use App\Enum\HttpMethod;
use App\Exceptions\RemotesiteCommunicationException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\App;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class Site extends Authenticatable
{
    protected $fillable = [
        'url',
        'key',
        'php_version',
        'db_type',
        'db_version',
        'cms_version',
        'server_os',
        'update_patch',
        'update_minor',
        'update_major'
    ];

    protected $hidden = [
        'key'
    ];

    protected function casts(): array
    {
        return [
            'last_seen' => 'datetime',
            'update_patch' => 'bool',
            'update_minor' => 'bool',
            'update_major' => 'bool'
        ];
    }

    public function getUrlAttribute(string $value): string
    {
        return rtrim($value, "/") . "/";
    }


    protected function performRequest(
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

    public function performExtractionRequest(array $data, string $password): array
    {
        $request = new Request(
            'POST',
            $this->url . 'extract.php'
        );

        $data['password'] = $password;

        return $this->performRequest(
            $request,
            [
                'form_params' => $data,
                'timeout' => 300.0
            ]
        );
    }

    public function performWebserviceRequest(HttpMethod $method, string $endpoint, array $data = []): array
    {
        $request = new Request(
            $method->name,
            $this->url . $endpoint,
            [
                'Authorization' => 'JUpdate-Token ' . $this->key
            ]
        );

        return $this->performRequest(
            $request,
            [
                "json" => $data
            ]
        );
    }
}
