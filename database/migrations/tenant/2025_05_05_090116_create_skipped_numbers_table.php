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
        Schema::create('skipped_numbers', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number');
            $table->foreignId('provider_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('agent_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('batch_id')->nullable()->index();
            $table->string('file_name')->nullable();
            $table->string('skip_reason');
            $table->integer('row_number')->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamps();

            // Create an index for quicker lookups
            $table->index(['phone_number', 'provider_id']);
            $table->index(['phone_number', 'agent_id']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('skipped_numbers');
    }
};
