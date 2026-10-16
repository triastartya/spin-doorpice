<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Winner;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $events = Event::withCount(['coupons', 'winners'])->latest()->get();
        $active = Event::whereIn('status', ['aktif', 'spin'])->first();
        $recent = Winner::with(['coupon.employee', 'prize', 'event'])->latest()->take(10)->get();

        return view('dashboard', compact('events', 'active', 'recent'));
    }
}
