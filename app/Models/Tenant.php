<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    protected $fillable = [
        'nombre',
        'phone_number_id',
        'webhook_token',
        'api_url',
        'api_secret',
        'activo',
    ];
}
