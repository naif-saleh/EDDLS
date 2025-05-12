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
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->string('slug');
            $table->string('call_id')->default(null); // Unique identifier for the call

            $table->boolean('is_ready_calling')->default(false);
            $table->foreignId('campaign_id')->constrained()->onDelete('cascade');
            $table->string('phone_number');
            $table->enum('status', ['new', 'calling', 'answer', 'no_answer'])->default('new');
            $table->dateTime('start_calling')->nullable();
            $table->dateTime('end_calling')->nullable();
            $table->integer('attempt_count')->default(0);
            $table->json('additional_data')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
