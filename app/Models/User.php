<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * The application does not use Laravel's built-in database authentication:
 * clerks authenticate through Supabase GoTrue via the server-side proxy in
 * App\Services\Supabase. This model is kept only so the framework's default
 * auth configuration remains valid.
 */
class User extends Authenticatable
{
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];
}
