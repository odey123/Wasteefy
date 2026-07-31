<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;

/**
 * Every authenticated User is treated as an admin/staff account — there is
 * no separate roles table. This policy exists as the single seam to add
 * per-role checks later without touching controllers.
 */
class ReportPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Report $report): bool
    {
        return true;
    }

    public function update(User $user, Report $report): bool
    {
        return true;
    }

    public function delete(User $user, Report $report): bool
    {
        return true;
    }
}
