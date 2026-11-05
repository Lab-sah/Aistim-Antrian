<?php

namespace App\Models;

use App\Enums\QueueStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Queue extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id', 'service_id', 'counter_id', 'number', 'status',
        'local_date', 'taken_at', 'called_at', 'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => QueueStatus::class,
            'local_date' => 'date',
            'taken_at' => 'datetime',
            'called_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function counter(): BelongsTo
    {
        return $this->belongsTo(Counter::class);
    }

    /** Format tampilan, misal A-001. */
    public function displayNumber(): string
    {
        return sprintf('%s-%03d', $this->service->prefix, $this->number);
    }
}
