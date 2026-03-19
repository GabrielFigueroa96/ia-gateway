<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('whatsapp_token')->nullable()->after('api_secret')->comment('Token de la API de WhatsApp para enviar mensajes');
            $table->text('mensaje_fallback')->nullable()->after('whatsapp_token')->comment('Mensaje a enviar si el API del negocio falla');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['whatsapp_token', 'mensaje_fallback']);
        });
    }
};
