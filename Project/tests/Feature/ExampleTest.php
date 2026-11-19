<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_backup_allows_admin_and_operator_and_downloads_valid_sqlite(): void
    {
        $this->post('/admin/database/backup')->assertRedirect('/login');
        $operator = User::factory()->create(['role' => 'operator']);
        $this->actingAs($operator)->get('/event')->assertOk()->assertSee('Backup Database');
        $this->actingAs(User::factory()->create(['role' => 'operator_hadiah']))->post('/admin/database/backup')->assertForbidden();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        DB::connection()->getPdo()->commit();
        try {
            $response = $this->post('/admin/database/backup')->assertOk();
            $path = $response->baseResponse->getFile()->getPathname();
            try {
                $this->assertStringContainsString('backup_database_', $response->headers->get('content-disposition'));
                $backup = new \PDO('sqlite:'.$path);
                $this->assertSame('ok', $backup->query('PRAGMA integrity_check')->fetchColumn());
                $this->assertSame(3, (int) $backup->query('SELECT count(*) FROM users')->fetchColumn());
            } finally {
                unlink($path);
            }
            $response = $this->actingAs($operator)->post('/admin/database/backup')->assertOk();
            unlink($response->baseResponse->getFile()->getPathname());
        } finally {
            DB::connection()->getPdo()->beginTransaction();
        }
    }

    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
