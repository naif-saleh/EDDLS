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
        Schema::create('dialer_calls_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('call_id')->default('null');
            $table->string('date_time');
            $table->string('provider');
            $table->string('campaign');
            $table->string('phone_number');
            $table->string('call_status');
            $table->string('dialing_duration')->default('null');
            $table->string('talking_duration')->default('null');
            $table->string('call_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dialer_calls_reports');
    }
};
