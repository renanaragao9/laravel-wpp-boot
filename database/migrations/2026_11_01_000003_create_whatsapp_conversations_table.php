<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_conversations', function (Blueprint $table) {
            $table->id();
            $table->string('driver');
            $table->string('phone');
            $table->string('push_name')->nullable();
            $table->string('status')->default('navigating');
            $table->dateTime('last_interaction_at')->nullable();
            $table->foreignId('current_node_id')->nullable()->constrained('whatsapp_menu_nodes')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['phone', 'status']);
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_conversations');
    }
};
