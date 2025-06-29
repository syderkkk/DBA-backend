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
        Schema::create('user_skins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('skin_code', 20);
            $table->timestamp('purchased_at')->useCurrent();
            $table->timestamps();
            
            $table->unique(['user_id', 'skin_code']);
            $table->foreign('skin_code')->references('skin_code')->on('character_skins');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_skins');
    }
};
