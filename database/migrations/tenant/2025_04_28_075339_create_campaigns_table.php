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
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('slug');
            $table->string('name');
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('provider_id')->constrained()->onDelete('cascade');
            $table->foreignId('agent_id')->nullable()->constrained()->onDelete('set null');
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->enum('status', ['not_start', 'calling', 'called', 'not_complete', 'processed'])->default('not_start');
            $table->boolean('allow')->default(true);
            $table->enum('campaign_type', ['dialer', 'distributor']);
            $table->integer('contact_count')->default(0);
            $table->string('batche_id')->nullable();
            $table->timestamps();


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
