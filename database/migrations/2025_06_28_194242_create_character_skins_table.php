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
        Schema::create('character_skins', function (Blueprint $table) {
            $table->id();
            $table->string('skin_code', 20)->unique();
            $table->string('name');
            $table->enum('character_type', ['Guerrero', 'Mago', 'Sanador']);
            $table->string('image_url')->nullable();
            $table->text('description')->nullable();
            $table->integer('price')->default(0);
            $table->boolean('is_available')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('character_skins');
    }
};
