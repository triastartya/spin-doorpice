<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Winner;
use App\Support\ActiveEvent;
use App\Exports\WinnersExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class WinnerController extends Controller
{
    public function index(Request $request, Event $event)
    {
        ActiveEvent::authorize($event);

        $query = Winner::with(['coupon.employee', 'prize'])->where('event_id', $event->id);

        if ($q = trim((string) $request->get('q'))) {
            $query->where(fn ($w) => $w->whereHas('coupon.employee', fn ($e) => $e->where('nik', 'like', "%{$q}%")->orWhere('name', 'like', "%{$q}%"))->orWhereHas('coupon', fn ($c) => $c->where('code', 'like', "%{$q}%")));
        }

        if ($prizeId = $request->get('prize_id')) {
            $query->where('prize_id', $prizeId);
        }

        if (in_array($request->get('status'), ['menang', 'diserahkan', 'dibatalkan'], true)) {
            $query->where('status', $request->get('status'));
        }

        $winners = $query->orderByDesc('id')->paginate(50)->withQueryString();

        return view('admin.winners', [
            'event' => $event,
            'winners' => $winners,
            'prizes' => $event->prizes()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function export(Event $event)
    {
        ActiveEvent::authorize($event);

        return Excel::download(new WinnersExport($event), 'pemenang-'.$event->id.'.xlsx');
    }
}
