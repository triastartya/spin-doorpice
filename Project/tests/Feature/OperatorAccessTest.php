<?php

namespace Tests\Feature;

use App\Exports\WinnersExport;
use App\Models\Coupon;
use App\Models\Employee;
use App\Models\Event;
use App\Models\Prize;
use App\Models\User;
use App\Models\Winner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Maatwebsite\Excel\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
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
            'pickup_location' => 'GIFT STATION 1',
        ])->assertRedirect();

        $prize = Prize::firstOrFail();
        $this->assertSame('GIFT STATION 1', $prize->pickup_location);
        $this->put("/admin/prizes/{$prize->id}", ['name' => 'Sepeda', 'category' => 'utama', 'stock' => 1, 'pickup_location' => 'Atas Panggung'])->assertRedirect();
        $this->assertSame('Atas Panggung', $prize->fresh()->pickup_location);
        $this->assertNotNull($prize->image_path);
        $this->assertFileExists(public_path($prize->image_path));
        @unlink(public_path($prize->image_path));
    }

    public function test_winners_export_contains_operator_and_photo(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);
        $event = Event::factory()->create(['status' => 'spin']);
        $employee = Employee::factory()->create();
        $coupon = Coupon::create([
            'event_id' => $event->id, 'employee_id' => $employee->id,
            'code' => Coupon::generateCode($event->id), 'gps_valid' => true,
        ]);
        $winner = Winner::create(['event_id' => $event->id, 'coupon_id' => $coupon->id, 'prize_id' => $event->prizes()->create(['name' => 'Umrah', 'category' => 'utama', 'stock' => 1])->id]);
        $photo = 'uploads/handover/test-export.png';
        File::ensureDirectoryExists(public_path('uploads/handover'));
        file_put_contents(public_path($photo), UploadedFile::fake()->image('bukti.png')->getContent());
        $winner->update(['status' => 'diserahkan', 'taken_at' => now(), 'taken_by' => $operator->id, 'photo_path' => $photo]);

        $file = tmpfile();
        try {
            fwrite($file, \Maatwebsite\Excel\Facades\Excel::raw(new WinnersExport($event), Excel::XLSX));
            $spreadsheet = IOFactory::load(stream_get_meta_data($file)['uri']);
            $sheet = $spreadsheet->getActiveSheet();
            $this->assertSame('Operator', $sheet->getCell('I1')->getValue());
            $this->assertSame($operator->name, $sheet->getCell('I2')->getValue());
            $this->assertSame('Foto Bukti', $sheet->getCell('J1')->getValue());
            $this->assertCount(1, $sheet->getDrawingCollection());
            $this->assertSame('J2', $sheet->getDrawingCollection()[0]->getCoordinates());
        } finally {
            fclose($file);
            @unlink(public_path($photo));
        }
    }
}
