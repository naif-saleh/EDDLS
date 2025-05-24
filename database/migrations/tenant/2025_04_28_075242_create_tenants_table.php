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
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name')->unique();
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('inactive');
            $table->string('api_key')->unique()->nullable();
            $table->string('database_name')->nullable();
            $table->string('database_username')->nullable();
            $table->string('database_password')->nullable();
            $table->boolean('database_created')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
