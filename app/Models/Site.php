<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

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
}
