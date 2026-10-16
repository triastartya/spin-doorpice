<?php

namespace App\Http\Controllers;

use App\Models\Prize;
use App\Models\Event;
use App\Support\ActiveEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;

class PrizeController extends Controller
{
    public function index(Event $event)
    {
        ActiveEvent::authorize($event);
        return view('admin.prizes', ['event' => $event, 'prizes' => $event->prizes()->withCount(['winners as taken' => fn ($q) => $q->whereIn('status', ['menang', 'diserahkan'])])->get()]);
    }

    public function store(Request $request, Event $event)
    {
        ActiveEvent::authorize($event);
        $data = $this->validated($request);
        unset($data['image']);
        $data['spin_duration'] = $data['spin_duration'] ?? 10;
        $data['last_digit_duration'] = $data['last_digit_duration'] ?? 5;
        $data['image_path'] = $this->upload($request);
        $event->prizes()->create($data);

        return back()->with('success', 'Hadiah ditambahkan.');
    }

    public function update(Request $request, Prize $prize)
    {
        ActiveEvent::authorize($prize->event);
        $data = $this->validated($request);
        unset($data['image']);
        $data['spin_duration'] = $data['spin_duration'] ?? 10;
        $data['last_digit_duration'] = $data['last_digit_duration'] ?? 5;
        if ($request->hasFile('image')) {
            $this->deleteImage($prize);
            $data['image_path'] = $this->upload($request);
        }
        $prize->update($data);

        return back()->with('success', 'Hadiah diperbarui.');
    }

    public function destroy(Prize $prize)
    {
        ActiveEvent::authorize($prize->event);
        $this->deleteImage($prize);
        $prize->delete();

        return back()->with('success', 'Hadiah dihapus.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required'],
            'category' => ['required', Rule::in(['massal', 'utama'])],
            'stock' => ['required', 'integer', 'min:1'],
            'spin_duration' => ['nullable', 'integer', 'min:5', 'max:60'],
            'last_digit_duration' => ['nullable', 'integer', 'min:1', 'max:60'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp'],
        ]);
    }

    protected function upload(Request $request): ?string
    {
        if (! $request->hasFile('image')) {
            return null;
        }

        $name = $request->file('image')->hashName();
        File::ensureDirectoryExists(public_path('uploads/prizes'));
        $request->file('image')->move(public_path('uploads/prizes'), $name);

        return 'uploads/prizes/'.$name;
    }

    protected function deleteImage(Prize $prize): void
    {
        if ($prize->image_path && File::exists(public_path($prize->image_path))) {
            File::delete(public_path($prize->image_path));
        }
    }
}
