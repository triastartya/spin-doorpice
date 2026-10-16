<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->enum('role', ['admin', 'operator'])->default('operator'));

        Schema::create('employees', function (Blueprint $t) {
            $t->id();
            $t->string('nik')->unique();
            $t->string('name');
            $t->string('department')->nullable();
            $t->string('email')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('events', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->enum('status', ['draft', 'aktif', 'spin', 'selesai'])->default('draft');
            $t->dateTime('absensi_buka')->nullable();
            $t->dateTime('absensi_tutup')->nullable();
            $t->decimal('latitude', 10, 7)->nullable();
            $t->decimal('longitude', 10, 7)->nullable();
            $t->unsignedInteger('radius_meter')->default(100);
            $t->string('display_token', 32)->unique();
            $t->timestamps();
        });

        Schema::create('prizes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('event_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->enum('category', ['massal', 'utama']);
            $t->unsignedInteger('stock');
            $t->timestamps();
        });

        Schema::create('coupons', function (Blueprint $t) {
            $t->id();
            $t->foreignId('event_id')->constrained()->cascadeOnDelete();
            $t->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $t->string('code', 6);
            $t->decimal('latitude', 10, 7)->nullable();
            $t->decimal('longitude', 10, 7)->nullable();
            $t->decimal('accuracy', 8, 2)->nullable();
            $t->boolean('gps_valid')->default(false);
            $t->timestamps();
            $t->unique(['event_id', 'employee_id']);
            $t->unique(['event_id', 'code']);
        });

        Schema::create('spins', function (Blueprint $t) {
            $t->id();
            $t->foreignId('event_id')->constrained()->cascadeOnDelete();
            $t->foreignId('prize_id')->constrained()->cascadeOnDelete();
            $t->enum('mode', ['utama', 'massal']);
            $t->foreignId('performed_by')->constrained('users');
            $t->unsignedInteger('winner_count')->default(0);
            $t->unsignedInteger('unserved_count')->default(0);
            $t->enum('status', ['berjalan', 'selesai'])->default('selesai');
            $t->timestamps();
        });

        Schema::create('winners', function (Blueprint $t) {
            $t->id();
            $t->foreignId('event_id')->constrained()->cascadeOnDelete();
            $t->foreignId('coupon_id')->unique()->constrained()->cascadeOnDelete();
            $t->foreignId('prize_id')->constrained()->cascadeOnDelete();
            $t->foreignId('spin_id')->nullable()->constrained()->nullOnDelete();
            $t->enum('status', ['menang', 'diserahkan', 'dibatalkan'])->default('menang');
            $t->timestamp('taken_at')->nullable();
            $t->foreignId('taken_by')->nullable()->constrained('users')->nullOnDelete();
            $t->text('cancel_reason')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('winners');
        Schema::dropIfExists('spins');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('prizes');
        Schema::dropIfExists('events');
        Schema::dropIfExists('employees');
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('role'));
    }
};
