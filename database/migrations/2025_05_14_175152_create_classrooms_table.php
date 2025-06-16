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
        Schema::create('classrooms', function (Blueprint $table) {
            $table->id();
            $table->string('title', 100);
            $table->string('description', 300);
            $table->string('join_code', 100)->unique();
            $table->unsignedBigInteger('professor_id');
            $table->unsignedInteger('max_capacity');
            $table->date('start_date');
            $table->date('expiration_date');
            $table->timestamps();

            $table->foreign('professor_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classrooms');
    }
};
