<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('message_logs', function (Blueprint $table) {
            $table->string('wamid')->nullable()->after('from')->comment('ID del mensaje en WhatsApp');
            // null = incoming | sent | delivered | read
            $table->string('status')->nullable()->after('wamid');
            $table->index('wamid');
        });
    }

    public function down(): void
    {
        Schema::table('message_logs', function (Blueprint $table) {
            $table->dropIndex(['wamid']);
            $table->dropColumn(['wamid', 'status']);
        });
    }
};
