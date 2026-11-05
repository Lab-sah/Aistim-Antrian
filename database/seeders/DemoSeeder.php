<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Counter;
use App\Models\CounterAssignment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Data contoh untuk pengembangan lokal. Aman dijalankan berulang.
 * Password demo JANGAN dipakai di produksi.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::updateOrCreate(
            ['code' => 'PST'],
            ['name' => 'Cabang Pusat', 'address' => 'Jl. Contoh No. 1', 'timezone' => 'Asia/Jakarta', 'is_active' => true],
        );

        Service::updateOrCreate(
            ['branch_id' => $branch->id, 'prefix' => 'A'],
            ['name' => 'Pendaftaran', 'is_active' => true],
        );
        Service::updateOrCreate(
            ['branch_id' => $branch->id, 'prefix' => 'B'],
            ['name' => 'Pembayaran', 'is_active' => true],
        );

        $loket1 = Counter::updateOrCreate(
            ['branch_id' => $branch->id, 'name' => 'Loket 1'],
            ['is_active' => true],
        );
        Counter::updateOrCreate(
            ['branch_id' => $branch->id, 'name' => 'Loket 2'],
            ['is_active' => true],
        );

        User::updateOrCreate(
            ['email' => 'admin@example.test'],
            ['name' => 'Super Admin', 'password' => 'password', 'role' => UserRole::SuperAdmin, 'branch_id' => null],
        );

        $officer = User::updateOrCreate(
            ['email' => 'petugas@example.test'],
            ['name' => 'Petugas Loket 1', 'password' => 'password', 'role' => UserRole::CounterOfficer, 'branch_id' => $branch->id],
        );

        CounterAssignment::updateOrCreate(
            ['user_id' => $officer->id, 'assigned_date' => Carbon::now($branch->timezone)->toDateString()],
            ['branch_id' => $branch->id, 'counter_id' => $loket1->id],
        );
    }
}
