<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->enum('role', ['admin', 'operator', 'operator_hadiah'])->default('operator')->change());
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'operator_hadiah')->update(['role' => 'operator']);
        Schema::table('users', fn (Blueprint $table) => $table->enum('role', ['admin', 'operator'])->default('operator')->change());
    }
};
