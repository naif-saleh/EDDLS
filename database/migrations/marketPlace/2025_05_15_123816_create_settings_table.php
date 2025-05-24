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
        Schema::create('settings', function (Blueprint $table) {
             $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->time('start_time')->comment('Operating hours start time');
            $table->time('end_time')->comment('Operating hours end time');
            $table->string('logo')->nullable()->comment('Logo URL');
            $table->integer('calls_at_time')->default(20)->comment('Number of calls at a time');
            $table->boolean('auto_call')->default(true);
            $table->timestamps();

            // Prevent duplicate settings for the same time range
            $table->unique(['tenant_id', 'start_time', 'end_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
