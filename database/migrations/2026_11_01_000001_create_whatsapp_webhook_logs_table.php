<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('driver');
            $table->string('event')->nullable();
            $table->string('instance')->nullable();
            $table->string('phone')->nullable();
            $table->string('push_name')->nullable();
            $table->string('message_id')->nullable();
            $table->string('message_type')->nullable();
            $table->string('option')->nullable();
            $table->string('status')->default('received');
            $table->text('text')->nullable();
            $table->text('error')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['phone', 'created_at']);
            $table->index(['driver', 'message_id']);
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_webhook_logs');
    }
};
