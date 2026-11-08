<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Subscriber extends Model
{
    protected $fillable = ['email', 'token', 'is_confirmed'];

    protected function casts(): array
    {
        return ['is_confirmed' => 'boolean'];
    }

    public static function newToken(): string
    {
        return Str::random(64);
    }
}
