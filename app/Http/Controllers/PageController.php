<?php

namespace App\Http\Controllers;

use App\Enums\QueueStatus;
use App\Models\Branch;
use App\Models\CounterAssignment;
use App\Models\Queue;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class PageController extends Controller
{
    /** Halaman kiosk pelanggan untuk satu layanan. */
    public function kiosk(Service $service): View
    {
        abort_unless($service->is_active, 404);

        return view('kiosk', ['service' => $service->load('branch')]);
    }

    /** Halaman loket: menampilkan loket yang ditugaskan hari ini. */
    public function loket(Request $request): View
    {
        $user = $request->user();
        $today = Carbon::now($user->branch?->timezone ?? 'Asia/Jakarta')->toDateString();

        $assignment = CounterAssignment::query()
            ->with('counter.branch')
            ->where('user_id', $user->id)
            ->whereDate('assigned_date', $today)
            ->first();

        return view('loket', [
            'counter' => $assignment?->counter,
            'branch' => $assignment?->counter->branch ?? $user->branch,
        ]);
    }

    /** Halaman display publik untuk satu cabang. */
    public function display(Branch $branch): View
    {
        return view('display', ['branch' => $branch]);
    }

    /** Data display untuk polling: antrian yang sedang/baru dipanggil hari ini. */
    public function displayData(Branch $branch): JsonResponse
    {
        $today = Carbon::now($branch->timezone)->toDateString();

        $called = Queue::query()
            ->with(['service', 'counter'])
            ->where('branch_id', $branch->id)
            ->whereDate('local_date', $today)
            ->where('status', QueueStatus::Called)
            ->orderByDesc('called_at')
            ->limit(10)
            ->get();

        return response()->json($called->map(fn (Queue $q) => [
            'display_number' => $q->displayNumber(),
            'counter' => $q->counter?->name,
            'called_at' => $q->called_at?->toIso8601String(),
        ]));
    }
}
