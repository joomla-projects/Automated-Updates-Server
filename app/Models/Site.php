<?php

declare(strict_types=1);

namespace App\Models;

use App\Remotesite\Connection;
use Illuminate\Database\Eloquent\Model;

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
        return new Connection($this->url, $this->key);
    }
}
