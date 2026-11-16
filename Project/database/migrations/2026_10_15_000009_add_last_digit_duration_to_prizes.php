<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prizes', function (Blueprint $t) {
            $t->unsignedSmallInteger('last_digit_duration')->default(5)->after('spin_duration');
        });
    }

    public function down(): void
    {
        Schema::table('prizes', fn (Blueprint $t) => $t->dropColumn('last_digit_duration'));
    }
};
