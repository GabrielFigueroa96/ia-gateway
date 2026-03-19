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
        Schema::create('message_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('from')->nullable()->comment('Número del cliente');
            $table->string('type')->default('text')->comment('text, image, audio, etc.');
            $table->text('message')->nullable()->comment('Texto del mensaje');
            $table->json('payload')->nullable()->comment('Payload completo de WhatsApp');
            $table->boolean('api_ok')->default(false)->comment('Si el reenvío al negocio fue exitoso');
            $table->unsignedSmallInteger('api_status')->nullable()->comment('HTTP status del negocio');
            $table->boolean('fallback_sent')->default(false)->comment('Si se envió mensaje de fallback');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
            $table->index(['tenant_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_logs');
    }
};
