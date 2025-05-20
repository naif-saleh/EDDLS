<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('database_name')->nullable()->after('api_key');
            $table->string('database_username')->nullable()->after('database_name');
            $table->string('database_password')->nullable()->after('database_username');
            $table->boolean('database_created')->default(false)->after('database_password');
        });
    }

    public function down()
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'database_name',
                'database_username',
                'database_password',
                'database_created'
            ]);
        });
    }
}; 