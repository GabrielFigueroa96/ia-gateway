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
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('phone_number_id')->unique()->comment('ID del número en Meta/WhatsApp');
            $table->string('webhook_token')->unique()->comment('Token para verificación del webhook');
            $table->string('api_url')->comment('URL del negocio que procesará el mensaje');
            $table->string('api_secret')->comment('Token para autenticar el reenvío al negocio');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
