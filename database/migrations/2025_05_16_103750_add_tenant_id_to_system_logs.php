<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('system_logs', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('user_id')->constrained()->onDelete('cascade');
        });

        // Update existing logs with tenant_id from their associated users
        DB::table('system_logs')
            ->join('users', 'system_logs.user_id', '=', 'users.id')
            ->whereNotNull('users.tenant_id')
            ->update(['system_logs.tenant_id' => DB::raw('users.tenant_id')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('system_logs', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropColumn('tenant_id');
        });
    }
}; 