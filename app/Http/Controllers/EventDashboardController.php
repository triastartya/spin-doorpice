<?php

namespace App\Http\Controllers;

use App\Support\ActiveEvent;

class EventDashboardController extends Controller
{
    public function __invoke()
    {
        $event = ActiveEvent::get();

        if (! $event) {
            return view('event-dashboard', ['event' => null]);
        }

        $event->loadCount('coupons', 'winners');

        return view('event-dashboard', [
            'event' => $event,
            'prizes' => $event->prizes()->withCount(['winners as taken' => fn ($q) => $q->whereIn('status', ['menang', 'diserahkan'])])->get(),
            'recentWinners' => $event->winners()->with(['coupon.employee', 'prize'])->latest()->take(8)->get(),
        ]);
    }
}
