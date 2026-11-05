<?php

namespace App\Services;

use App\Enums\QueueStatus;
use App\Models\Counter;
use App\Models\Queue;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use RuntimeException;

class QueueService
{
    /**
     * Ambil nomor baru untuk sebuah layanan. Nomor berurutan per cabang,
     * per layanan, per hari (mengikuti timezone cabang).
     */
    public function takeNumber(Service $service): Queue
    {
        $branch = $service->branch;
        $today = Carbon::now($branch->timezone)->toDateString();

        return DB::transaction(function () use ($service, $branch, $today) {
            // Kunci baris layanan supaya dua pelanggan tidak mendapat nomor sama.
            Service::whereKey($service->id)->lockForUpdate()->first();

            $last = Queue::query()
                ->where('service_id', $service->id)
                ->whereDate('local_date', $today)
                ->max('number');

            return Queue::create([
                'branch_id' => $branch->id,
                'service_id' => $service->id,
                'number' => ($last ?? 0) + 1,
                'status' => QueueStatus::Waiting,
                'local_date' => $today,
                'taken_at' => now(),
            ])->load('service');
        });
    }

    /**
     * Panggil antrian "waiting" tertua di cabang user, lalu kaitkan dengan loket.
     */
    public function callNext(User $user, Counter $counter): ?Queue
    {
        $this->ensureAssignedToday($user, $counter);

        return DB::transaction(function () use ($user, $counter) {
            $queue = Queue::query()
                ->where('branch_id', $counter->branch_id)
                ->where('status', QueueStatus::Waiting)
                ->whereDate('local_date', $this->today($counter))
                ->orderBy('taken_at')
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if ($queue === null) {
                return null;
            }

            $queue->update([
                'status' => QueueStatus::Called,
                'counter_id' => $counter->id,
                'called_at' => now(),
            ]);

            return $queue->load('service', 'counter');
        });
    }

    /**
     * Ubah status antrian menjadi served atau skipped.
     */
    public function finish(Queue $queue, QueueStatus $status): Queue
    {
        if (! in_array($status, [QueueStatus::Served, QueueStatus::Skipped], true)) {
            throw new RuntimeException('Status tujuan harus served atau skipped.');
        }

        if ($queue->status !== QueueStatus::Called) {
            throw new RuntimeException('Hanya antrian yang sedang dipanggil yang bisa diselesaikan.');
        }

        $queue->update([
            'status' => $status,
            'finished_at' => now(),
        ]);

        return $queue;
    }

    private function ensureAssignedToday(User $user, Counter $counter): void
    {
        $assigned = $user->isSuperAdmin() || $counter->assignments()
            ->where('user_id', $user->id)
            ->whereDate('assigned_date', $this->today($counter))
            ->exists();

        if (! $assigned) {
            throw new RuntimeException('Petugas tidak ditugaskan di loket ini hari ini.');
        }
    }

    private function today(Counter $counter): string
    {
        return Carbon::now($counter->branch->timezone)->toDateString();
    }
}
