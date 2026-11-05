<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case BranchAdmin = 'branch_admin';
    case CounterOfficer = 'counter_officer';

    /** Super admin tidak terikat cabang; peran lain wajib punya branch_id. */
    public function requiresBranch(): bool
    {
        return $this !== self::SuperAdmin;
    }
}
