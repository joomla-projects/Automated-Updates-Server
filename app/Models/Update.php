<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Update extends Model
{
    protected $fillable = [
        'old_version',
        'new_version',
        'result',
        'failed_step',
        'failed_message',
        'failed_trace'
    ];

    protected function casts(): array
    {
        return [
            'result' => 'bool'
        ];
    }
}
