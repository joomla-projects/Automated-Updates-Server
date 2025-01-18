<?php

declare(strict_types=1);

namespace App\Models;

use App\RemoteSite\Connection;
use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\App;

class Site extends Model
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
        return rtrim($value, "/");
    }

    public function getConnectionAttribute(): Connection
    {
        return App::makeWith(
            Connection::class,
            ["baseUrl" => $this->url, "key" => $this->key]
        );
    }

    public function getFrontendStatus(): int
    {
        /** @var Client $httpClient */
        $httpClient = App::make(Client::class);

        return $httpClient->get($this->url)->getStatusCode();
    }

    /**
     * @return HasMany<Update, $this>
     */
    public function updates(): HasMany
    {
        return $this->hasMany(Update::class, 'site_id', 'id');
    }
}
