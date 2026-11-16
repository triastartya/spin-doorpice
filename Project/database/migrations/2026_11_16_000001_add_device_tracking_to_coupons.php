<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $t) {
            $t->string('device_token')->nullable()->after('attendance_method');
            $t->text('device_agent')->nullable()->after('device_token');
            $t->string('device_ip', 45)->nullable()->after('device_agent');
            $t->unique(['event_id', 'device_token']);
        });
    }

    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $t) {
            $t->dropUnique(['event_id', 'device_token']);
            $t->dropColumn(['device_token', 'device_agent', 'device_ip']);
        });
    }
};
