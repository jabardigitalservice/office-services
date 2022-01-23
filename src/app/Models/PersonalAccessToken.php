<?php

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    public $incrementing = false;

    protected $connection = 'mysql';

    protected $fillable = [
        'id',
        'name',
        'token',
        'abilities',
        'fcm_token',
        'last_used_at',
    ];
}
