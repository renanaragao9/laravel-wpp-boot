<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_menu_nodes', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->string('option_number')->nullable();
            $table->string('type')->default('menu');
            $table->string('sector')->nullable();
            $table->text('message')->nullable();
            $table->boolean('active')->default(true);
            $table->foreignId('parent_id')->nullable()->constrained('whatsapp_menu_nodes')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['parent_id', 'option_number']);
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_menu_nodes');
    }
};
