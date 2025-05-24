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
       Schema::create('system_logs', function (Blueprint $table) {
            $table->id();
            $table->string('log_type')->index()->comment('Type of log: create, update, delete, login, etc.');
            $table->string('model_type')->nullable()->index()->comment('Model class name: Agent, Provider, etc.');
            $table->unsignedBigInteger('model_id')->nullable()->index()->comment('Primary key of the affected model');
            $table->unsignedBigInteger('user_id')->nullable()->index()->comment('User who performed the action');
            $table->string('action')->index()->comment('Specific action taken');
            $table->text('description')->nullable()->comment('Human-readable description of the change');
            $table->text('previous_data')->nullable()->comment('JSON of old values before change');
            $table->text('new_data')->nullable()->comment('JSON of new values after change');
            $table->text('metadata')->nullable()->comment('Additional contextual information');
            $table->string('ip_address')->nullable()->comment('IP address of the user');
            $table->string('user_agent')->nullable()->comment('User agent of the browser');
            $table->foreignId('tenant_id')->nullable()->constrained()->onDelete('cascade')->comment('Foreign key to the tenants table');
            $table->timestamps();

            // Index for efficient searching
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_logs');
    }
};
