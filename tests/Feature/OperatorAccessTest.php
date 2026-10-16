<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Prize;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class OperatorAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_can_manage_active_event_but_not_create_events(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);
        $active = Event::factory()->create(['status' => 'aktif']);
        $draft = Event::factory()->create(['status' => 'draft']);

        $this->actingAs($operator)->get('/event')->assertOk()->assertSee($active->name);
        $this->get("/admin/events/{$active->id}/edit")->assertOk();
        $this->get("/admin/events/{$draft->id}/edit")->assertForbidden();
        $this->get('/admin/events/create')->assertForbidden();
        $this->post('/admin/events', [])->assertForbidden();
    }

    public function test_operator_can_set_status_aktif_but_not_draft(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);
        $event = Event::factory()->create(['status' => 'spin']);

        $this->actingAs($operator)->put("/admin/events/{$event->id}", [
            'name' => $event->name, 'status' => 'draft', 'radius_meter' => 100,
        ])->assertSessionHasErrors('status');

        $this->put("/admin/events/{$event->id}", [
            'name' => $event->name, 'status' => 'aktif', 'radius_meter' => 100,
        ])->assertRedirect();
        $this->assertDatabaseHas('events', ['id' => $event->id, 'status' => 'aktif']);
    }

    public function test_operator_can_manage_global_employees(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);

        $this->actingAs($operator)->get('/admin/employees')->assertOk();
        $this->post('/admin/employees', ['nik' => 'EMP001', 'name' => 'Karyawan Uji', 'department' => 'IT', 'is_active' => 1])->assertRedirect();
        $this->assertDatabaseHas('employees', ['nik' => 'EMP001']);
    }

    public function test_prize_image_uploads_directly_to_public(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $event = Event::factory()->create(['status' => 'aktif']);
        $image = UploadedFile::fake()->image('hadiah.jpg');

        $this->actingAs($admin)->post("/admin/events/{$event->id}/prizes", [
            'name' => 'Sepeda',
            'category' => 'utama',
            'stock' => 1,
            'image' => $image,
        ])->assertRedirect();

        $prize = Prize::firstOrFail();
        $this->assertNotNull($prize->image_path);
        $this->assertFileExists(public_path($prize->image_path));
        @unlink(public_path($prize->image_path));
    }
}
