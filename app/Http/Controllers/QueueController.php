<?php

namespace App\Http\Controllers;

use App\Enums\QueueStatus;
use App\Models\Counter;
use App\Models\Queue;
use App\Models\Service;
use App\Services\QueueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

class QueueController extends Controller
{
    public function __construct(private QueueService $queues)
    {
    }

    /** Pelanggan mengambil nomor untuk satu layanan (kiosk, tanpa login). */
    public function store(Service $service): JsonResponse
    {
        abort_unless($service->is_active, 404);

        $queue = $this->queues->takeNumber($service);

        return response()->json($this->present($queue), 201);
    }

    /** Petugas memanggil nomor berikutnya di loket miliknya. */
    public function callNext(Request $request): JsonResponse
    {
        $data = $request->validate([
            'counter_id' => ['required', 'integer', 'exists:counters,id'],
        ]);

        $counter = Counter::with('branch')->findOrFail($data['counter_id']);
        $this->authorizeBranch($request->user(), $counter->branch_id);

        try {
            $queue = $this->queues->callNext($request->user(), $counter);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        if ($queue === null) {
            return response()->json(['message' => 'Tidak ada antrian yang menunggu.'], 404);
        }

        return response()->json($this->present($queue));
    }

    /** Petugas menandai antrian yang dipanggil sebagai served atau skipped. */
    public function updateStatus(Request $request, Queue $queue): JsonResponse
    {
        $this->authorizeBranch($request->user(), $queue->branch_id);

        $data = $request->validate([
            'status' => ['required', Rule::in([QueueStatus::Served->value, QueueStatus::Skipped->value])],
        ]);

        try {
            $queue = $this->queues->finish($queue, QueueStatus::from($data['status']));
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($this->present($queue->load('service')));
    }

    /** Super admin boleh semua cabang; peran lain hanya cabangnya sendiri. */
    private function authorizeBranch($user, int $branchId): void
    {
        abort_unless(
            $user->isSuperAdmin() || $user->branch_id === $branchId,
            403,
            'Antrian ini bukan milik cabang Anda.'
        );
    }

    private function present(Queue $queue): array
    {
        $queue->loadMissing('service');

        return [
            'id' => $queue->id,
            'display_number' => $queue->displayNumber(),
            'service_name' => $queue->service->name,
            'status' => $queue->status->value,
            'counter_id' => $queue->counter_id,
            'local_date' => $queue->local_date->toDateString(),
            'taken_at' => $queue->taken_at?->toIso8601String(),
            'called_at' => $queue->called_at?->toIso8601String(),
            'finished_at' => $queue->finished_at?->toIso8601String(),
        ];
    }
}
