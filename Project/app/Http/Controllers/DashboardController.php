<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Winner;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    public function backup()
    {
        $connection = DB::connection();
        abort_unless($connection->getDriverName() === 'sqlite', 422, 'Backup ini hanya mendukung SQLite.');
        $path = storage_path('app/backup-'.Str::uuid().'.sqlite');

        try {
            $connection->getPdo()->exec('VACUUM INTO '.$connection->getPdo()->quote($path));

            return response()->download($path, 'backup_database_'.now('Asia/Jakarta')->format('Y-m-d_H-i-s').'.sqlite', [
                'Cache-Control' => 'private, no-store',
            ])->deleteFileAfterSend(true);
        } catch (\Throwable $e) {
            if (is_file($path)) {
                unlink($path);
            }
            throw $e;
        }
    }

    public function __invoke()
    {
        $events = Event::withCount(['coupons', 'winners'])->latest()->get();
        $active = Event::whereIn('status', ['aktif', 'spin'])->first();
        $recent = Winner::with(['coupon.employee', 'prize', 'event'])->latest()->take(10)->get();

        return view('dashboard', compact('events', 'active', 'recent'));
    }
}
