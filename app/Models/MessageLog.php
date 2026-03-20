<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessageLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'canal',
        'from',
        'wamid',
        'status',
        'type',
        'message',
        'payload',
        'api_ok',
        'api_status',
        'fallback_sent',
    ];

    protected $casts = [
        'payload'       => 'array',
        'api_ok'        => 'boolean',
        'fallback_sent' => 'boolean',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
