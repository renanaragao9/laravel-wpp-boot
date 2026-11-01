<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_demand_messages', function (Blueprint $table) {
            $table->id();
            $table->string('direction')->default('inbound');
            $table->string('message_id')->nullable();
            $table->text('body')->nullable();
            $table->dateTime('read_at')->nullable();
            $table->foreignId('demand_id')->constrained('whatsapp_demands')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['demand_id', 'created_at']);
            $table->index(['direction', 'read_at']);
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_demand_messages');
    }
};
