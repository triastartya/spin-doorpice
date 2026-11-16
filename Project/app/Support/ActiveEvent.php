<?php

namespace App\Support;

use App\Models\Event;

class ActiveEvent
{
    public static function get(): ?Event
    {
        return Event::whereIn('status', ['aktif', 'spin'])->latest('id')->first();
    }

    public static function authorize(Event $event): void
    {
        if (in_array(auth()->user()?->role, ['operator', 'operator_hadiah'], true) && self::get()?->isNot($event)) {
            abort(403, 'Operator hanya dapat mengakses acara yang aktif.');
        }
    }
}
