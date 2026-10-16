<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prizes', function (Blueprint $t) {
            $t->unsignedSmallInteger('spin_duration')->default(10)->after('stock');
        });
    }

    public function down(): void
    {
        Schema::table('prizes', fn (Blueprint $t) => $t->dropColumn('spin_duration'));
    }
};
