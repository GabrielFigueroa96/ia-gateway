<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    protected $fillable = [
        'nombre',
        'tipo_ia',
        'phone_number_id',
        'webhook_token',
        'api_url',
        'url_admin',
        'api_secret',
        'whatsapp_token',
        'mensaje_fallback',
        'activo',
    ];
}
