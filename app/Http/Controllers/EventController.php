<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Support\ActiveEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EventController extends Controller
{
    public function index()
    {
        return view('admin.events', ['events' => Event::withCount(['coupons', 'winners'])->latest()->get()]);
    }

    public function create()
    {
        return view('admin.event-form', ['event' => new Event]);
    }

    public function edit(Event $event)
    {
        ActiveEvent::authorize($event);

        return view('admin.event-form', compact('event'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $this->ensureOnlyOneActive($data['status']);
        $data['display_token'] = Str::random(24);
        Event::create($data);

        return redirect('/admin/events')->with('success', 'Acara ditambahkan.');
    }

    public function update(Request $request, Event $event)
    {
        ActiveEvent::authorize($event);
        $data = $this->validated($request);
        $this->ensureOnlyOneActive($data['status'], $event->id);

        if ($request->user()->role === 'operator' && $data['status'] === 'draft') {
            throw ValidationException::withMessages(['status' => 'Operator tidak dapat mengubah status menjadi draft.']);
        }

        $event->update($data);

        return redirect()->back()->with('success', 'Acara diperbarui.');
    }

    public function destroy(Event $event)
    {
        $event->delete();

        return back()->with('success', 'Acara dihapus.');
    }

    public function rotate(Event $event)
    {
        ActiveEvent::authorize($event);
        $event->update(['display_token' => Str::random(24)]);

        return back()->with('success', 'URL display diperbarui.');
    }

    protected function ensureOnlyOneActive(string $status, ?int $ignore = null): void
    {
        if (! in_array($status, ['aktif', 'spin'], true)) {
            return;
        }

        $exists = Event::whereIn('status', ['aktif', 'spin'])->when($ignore, fn ($q) => $q->where('id', '!=', $ignore))->exists();

        if ($exists) {
            throw ValidationException::withMessages(['status' => 'Hanya satu acara boleh aktif atau spin.']);
        }
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required'],
            'status' => ['required', Rule::in(['draft', 'aktif', 'spin', 'selesai'])],
            'absensi_buka' => ['nullable', 'date'],
            'absensi_tutup' => ['nullable', 'date', 'after_or_equal:absensi_buka'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'radius_meter' => ['required', 'integer', 'min:1'],
        ]);
    }
}
