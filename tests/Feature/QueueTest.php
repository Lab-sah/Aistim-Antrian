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

class QueueTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;
    private Service $service;
    private Counter $counter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::create([
            'name' => 'Pusat', 'code' => 'PST', 'timezone' => 'Asia/Jakarta', 'is_active' => true,
        ]);
        $this->service = Service::create([
            'branch_id' => $this->branch->id, 'name' => 'Pendaftaran', 'prefix' => 'A', 'is_active' => true,
        ]);
        $this->counter = Counter::create([
            'branch_id' => $this->branch->id, 'name' => 'Loket 1', 'is_active' => true,
        ]);
    }

    private function officer(Branch $branch, bool $assignedToCounter = true): User
    {
        $user = User::create([
            'name' => 'Petugas',
            'email' => uniqid().'@example.test',
            'password' => 'secret-password',
            'role' => UserRole::CounterOfficer,
            'branch_id' => $branch->id,
        ]);

        if ($assignedToCounter) {
            CounterAssignment::create([
                'branch_id' => $branch->id,
                'user_id' => $user->id,
                'counter_id' => $this->counter->id,
                'assigned_date' => Carbon::now($branch->timezone)->toDateString(),
            ]);
        }

        return $user;
    }

    public function test_kiosk_takes_sequential_numbers_per_service(): void
    {
        $first = $this->postJson("/services/{$this->service->id}/queues");
        $first->assertCreated()
            ->assertJsonPath('display_number', 'A-001')
            ->assertJsonPath('status', 'waiting');

        $this->postJson("/services/{$this->service->id}/queues")
            ->assertCreated()
            ->assertJsonPath('display_number', 'A-002');
    }

    public function test_numbers_reset_on_a_new_local_day(): void
    {
        Queue::create([
            'branch_id' => $this->branch->id,
            'service_id' => $this->service->id,
            'number' => 5,
            'status' => QueueStatus::Served,
            'local_date' => Carbon::now('Asia/Jakarta')->subDay()->toDateString(),
            'taken_at' => now()->subDay(),
        ]);

        $this->postJson("/services/{$this->service->id}/queues")
            ->assertCreated()
            ->assertJsonPath('display_number', 'A-001');
    }

    public function test_inactive_service_does_not_issue_numbers(): void
    {
        $this->service->update(['is_active' => false]);

        $this->postJson("/services/{$this->service->id}/queues")->assertNotFound();
    }

    public function test_call_next_requires_login(): void
    {
        $this->postJson('/queues/call-next', ['counter_id' => $this->counter->id])
            ->assertUnauthorized();
    }

    public function test_officer_must_be_assigned_to_the_counter(): void
    {
        $this->postJson("/services/{$this->service->id}/queues");

        $officer = $this->officer($this->branch, assignedToCounter: false);

        $this->actingAs($officer)
            ->postJson('/queues/call-next', ['counter_id' => $this->counter->id])
            ->assertForbidden();
    }

    public function test_officer_calls_oldest_waiting_queue_then_marks_served(): void
    {
        $this->postJson("/services/{$this->service->id}/queues")->assertCreated();
        $this->postJson("/services/{$this->service->id}/queues")->assertCreated();

        $officer = $this->officer($this->branch);

        $called = $this->actingAs($officer)
            ->postJson('/queues/call-next', ['counter_id' => $this->counter->id]);

        $called->assertOk()
            ->assertJsonPath('display_number', 'A-001')
            ->assertJsonPath('status', 'called')
            ->assertJsonPath('counter_id', $this->counter->id);

        $this->actingAs($officer)
            ->patchJson('/queues/'.$called->json('id').'/status', ['status' => 'served'])
            ->assertOk()
            ->assertJsonPath('status', 'served');
    }

    public function test_call_next_returns_not_found_when_queue_is_empty(): void
    {
        $officer = $this->officer($this->branch);

        $this->actingAs($officer)
            ->postJson('/queues/call-next', ['counter_id' => $this->counter->id])
            ->assertNotFound();
    }

    public function test_officer_cannot_update_queue_from_another_branch(): void
    {
        $this->postJson("/services/{$this->service->id}/queues");
        $queue = Queue::firstOrFail();

        $otherBranch = Branch::create([
            'name' => 'Cabang B', 'code' => 'CBB', 'timezone' => 'Asia/Jakarta', 'is_active' => true,
        ]);
        $officer = $this->officer($otherBranch, assignedToCounter: false);

        $this->actingAs($officer)
            ->patchJson("/queues/{$queue->id}/status", ['status' => 'skipped'])
            ->assertForbidden();
    }

    public function test_only_called_queue_can_be_finished(): void
    {
        $this->postJson("/services/{$this->service->id}/queues");
        $queue = Queue::firstOrFail();

        $officer = $this->officer($this->branch);

        $this->actingAs($officer)
            ->patchJson("/queues/{$queue->id}/status", ['status' => 'served'])
            ->assertUnprocessable();
    }
}
