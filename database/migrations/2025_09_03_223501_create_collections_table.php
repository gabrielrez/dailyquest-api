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
        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->string('name')->max(45);
            $table->text('description')->nullable()->max(255);
            $table->boolean('cyclic')->default(false);
            $table->date('deadline')->nullable();
            $table->boolean('is_collaborative')->default(false);
            $table->string('status')->default('in_progress');
            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');
            $table->date('expired_at')->nullable();
            $table->timestamps();

            $table->unique(['owner_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collections');
    }
};
