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
        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('license_key')->unique();
            $table->dateTime('valid_from');
            $table->dateTime('valid_until');
            $table->bigInteger('max_campaigns')->default(0);
            $table->bigInteger('max_agents')->default(0);
            $table->bigInteger('max_providers')->default(0);
            $table->bigInteger('max_dist_calls')->default(0);
            $table->bigInteger('max_dial_calls')->default(0);
            $table->bigInteger('max_contacts_per_campaign')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('licenses');
    }
};
