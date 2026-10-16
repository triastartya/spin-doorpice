<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('coupons', 'sequence')) {
            Schema::table('coupons', fn (Blueprint $t) => $t->unsignedInteger('sequence')->nullable()->after('code'));
        }

        DB::table('coupons')->orderBy('id')->get()->each(function ($coupon, $i) {
            $seq = $i + 1;
            $code = 'AST'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
            DB::table('coupons')->where('id', $coupon->id)->update(['sequence' => $seq, 'code' => $code]);
        });
    }

    public function down(): void
    {
        Schema::table('coupons', fn (Blueprint $t) => $t->dropColumn('sequence'));
    }
};
