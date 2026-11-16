<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_can_manage_operator_accounts(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);
        $target = User::factory()->create(['role' => 'operator']);

        $this->actingAs($operator)->get('/admin/users')->assertOk();

        $this->post('/admin/users', ['name' => 'Baru', 'email' => 'baru@example.com', 'password' => 'rahasia', 'role' => 'operator_hadiah'])->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => 'baru@example.com', 'role' => 'operator_hadiah']);

        $this->put("/admin/users/{$target->id}", ['name' => 'Diubah', 'email' => $target->email, 'role' => 'operator_hadiah'])->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $target->id, 'name' => 'Diubah', 'role' => 'operator_hadiah']);
        $this->post('/admin/users', ['name' => 'Admin', 'email' => 'forbidden@example.com', 'password' => 'rahasia', 'role' => 'admin'])->assertSessionHasErrors('role');
        $this->put("/admin/users/{$target->id}", ['name' => 'Admin', 'email' => $target->email, 'role' => 'admin'])->assertSessionHasErrors('role');
        $this->delete("/admin/users/{$target->id}")->assertRedirect();
        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    public function test_operator_cannot_manage_admin_accounts(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($operator)->put("/admin/users/{$admin->id}", ['name' => 'X', 'email' => $admin->email, 'role' => 'admin'])->assertForbidden();
        $this->delete("/admin/users/{$admin->id}")->assertForbidden();
    }

    public function test_operator_hadiah_only_has_dashboard_handover_and_winners(): void
    {
        $op = User::factory()->create(['role' => 'operator_hadiah']);
        $active = Event::factory()->create(['status' => 'aktif']);
        $done = Event::factory()->create(['status' => 'selesai']);

        $this->post('/login', ['email' => $op->email, 'password' => 'password'])->assertRedirect('/dashboard');
        $this->get('/dashboard')->assertOk()
            ->assertSee('Penyerahan')->assertSee('Pemenang')
            ->assertDontSee('href="'.url('operator').'"', false)
            ->assertDontSee('href="'.url('admin/users').'"', false)
            ->assertDontSee('href="'.url('admin/employees').'"', false);
        $this->get('/operator/kupon')->assertOk();
        $this->post('/operator/kupon/handover', [])->assertSessionHasErrors('code');
        $this->get("/admin/events/{$active->id}/winners")->assertOk();
        $this->get("/admin/events/{$active->id}/winners/export")->assertOk();

        $this->get('/event')->assertForbidden();
        $this->get('/operator')->assertForbidden();
        $this->get('/operator/absensi-manual')->assertForbidden();
        $this->post('/operator/spin')->assertForbidden();
        $this->get('/admin/employees')->assertForbidden();
        $this->get('/admin/users')->assertForbidden();
        $this->get("/admin/events/{$active->id}/edit")->assertForbidden();
        $this->get("/admin/events/{$active->id}/prizes")->assertForbidden();
        $this->get("/admin/events/{$active->id}/attendance")->assertForbidden();
        $this->get("/admin/events/{$done->id}/winners")->assertForbidden();
    }

    public function test_admin_can_create_admin_and_cannot_delete_self(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post('/admin/users', ['name' => 'Admin 2', 'email' => 'admin2@example.com', 'password' => 'rahasia', 'role' => 'admin'])->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => 'admin2@example.com', 'role' => 'admin']);

        $this->delete("/admin/users/{$admin->id}")->assertSessionHasErrors();
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }
}
