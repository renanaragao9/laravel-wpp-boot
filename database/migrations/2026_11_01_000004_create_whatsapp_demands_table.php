<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_demands', function (Blueprint $table) {
            $table->id();
            $table->string('driver');
            $table->string('phone');
            $table->string('push_name')->nullable();
            $table->string('sector');
            $table->string('title');
            $table->string('status')->default('open');
            $table->text('notes')->nullable();
            $table->integer('position')->default(0);
            $table->foreignId('conversation_id')->nullable()->constrained('whatsapp_conversations')->nullOnDelete();
            $table->foreignId('menu_node_id')->nullable()->constrained('whatsapp_menu_nodes')->nullOnDelete();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'position']);
            $table->index('sector');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_demands');
    }
};
