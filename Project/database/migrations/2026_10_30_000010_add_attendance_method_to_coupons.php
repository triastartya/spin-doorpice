<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $t) {
            $t->enum('attendance_method', ['gps', 'manual'])->default('gps')->after('gps_valid');
        });
    }

    public function down(): void
    {
        Schema::table('coupons', fn (Blueprint $t) => $t->dropColumn('attendance_method'));
    }
};
