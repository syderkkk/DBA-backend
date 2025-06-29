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
        Schema::create('user_classroom_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('classroom_id')->constrained()->onDelete('cascade');
            $table->integer('hp')->default(100);
            $table->integer('max_hp')->default(100);
            $table->integer('mp')->default(100);     // AGREGAR MP
            $table->integer('max_mp')->default(100); // AGREGAR MP máximo
            $table->timestamps();
            
            $table->unique(['user_id', 'classroom_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_classroom_stats');
    }
};
