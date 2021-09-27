<?php
namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    protected $connection = 'mysql';

    protected $fillable = [
        'name',
        'token',
        'abilities',
        'fcm_token',
        'last_used_at',
    ];
}
