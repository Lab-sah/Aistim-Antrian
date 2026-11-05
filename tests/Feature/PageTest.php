<?php

namespace Tests\Feature;

use App\Enums\QueueStatus;
use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Counter;
use App\Models\CounterAssignment;
use App\Models\Queue;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PageTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;
    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::create([
            'name' => 'Pusat', 'code' => 'PST', 'timezone' => 'Asia/Jakarta', 'is_active' => true,
        ]);
        $this->service = Service::create([
            'branch_id' => $this->branch->id, 'name' => 'Pendaftaran', 'prefix' => 'A', 'is_active' => true,
        ]);
    }

    public function test_kiosk_page_renders_for_active_service(): void
    {
        $this->get("/kiosk/{$this->service->id}")
            ->assertOk()
            ->assertSee('Ambil Nomor')
            ->assertSee('Pendaftaran');
    }

    public function test_kiosk_page_is_hidden_for_inactive_service(): void
    {
        $this->service->update(['is_active' => false]);

        $this->get("/kiosk/{$this->service->id}")->assertNotFound();
    }

    public function test_display_data_lists_called_queues_for_today_only(): void
    {
        $counter = Counter::create([
            'branch_id' => $this->branch->id, 'name' => 'Loket 1', 'is_active' => true,
        ]);

        Queue::create([
            'branch_id' => $this->branch->id,
            'service_id' => $this->service->id,
            'counter_id' => $counter->id,
            'number' => 1,
            'status' => QueueStatus::Called,
            'local_date' => Carbon::now('Asia/Jakarta')->toDateString(),
            'taken_at' => now(),
            'called_at' => now(),
        ]);
        Queue::create([
            'branch_id' => $this->branch->id,
            'service_id' => $this->service->id,
            'counter_id' => $counter->id,
            'number' => 2,
            'status' => QueueStatus::Called,
            'local_date' => Carbon::now('Asia/Jakarta')->subDay()->toDateString(),
            'taken_at' => now()->subDay(),
            'called_at' => now()->subDay(),
        ]);

        $this->getJson("/display/{$this->branch->id}/data")
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.display_number', 'A-001')
            ->assertJsonPath('0.counter', 'Loket 1');
    }

    public function test_display_page_renders_for_branch(): void
    {
        $this->get("/display/{$this->branch->id}")
            ->assertOk()
            ->assertSee('Pusat')
            ->assertSee('id="list"', false);
    }

    public function test_loket_page_requires_login(): void
    {
        $this->get('/loket')->assertRedirect('/login');
    }

    public function test_officer_can_log_in_and_is_sent_to_loket(): void
    {
        $user = User::create([
            'name' => 'Petugas', 'email' => 'p@example.test', 'password' => 'secret-password',
            'role' => UserRole::CounterOfficer, 'branch_id' => $this->branch->id,
        ]);

        $this->post('/login', ['email' => 'p@example.test', 'password' => 'secret-password'])
            ->assertRedirect(route('loket'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_is_rejected(): void
    {
        User::create([
            'name' => 'Petugas', 'email' => 'p@example.test', 'password' => 'secret-password',
            'role' => UserRole::CounterOfficer, 'branch_id' => $this->branch->id,
        ]);

        $this->post('/login', ['email' => 'p@example.test', 'password' => 'salah'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_loket_page_shows_message_without_assignment(): void
    {
        $user = User::create([
            'name' => 'Petugas', 'email' => 'p@example.test', 'password' => 'secret-password',
            'role' => UserRole::CounterOfficer, 'branch_id' => $this->branch->id,
        ]);

        $this->actingAs($user)->get('/loket')
            ->assertOk()
            ->assertSee('Belum ada loket');
    }

    public function test_loket_page_shows_assigned_counter(): void
    {
        $counter = Counter::create([
            'branch_id' => $this->branch->id, 'name' => 'Loket 1', 'is_active' => true,
        ]);
        $user = User::create([
            'name' => 'Petugas', 'email' => 'p@example.test', 'password' => 'secret-password',
            'role' => UserRole::CounterOfficer, 'branch_id' => $this->branch->id,
        ]);
        CounterAssignment::create([
            'branch_id' => $this->branch->id,
            'user_id' => $user->id,
            'counter_id' => $counter->id,
            'assigned_date' => Carbon::now('Asia/Jakarta')->toDateString(),
        ]);

        $this->actingAs($user)->get('/loket')
            ->assertOk()
            ->assertSee('Panggil Berikutnya')
            ->assertSee('Loket 1');
    }
}
