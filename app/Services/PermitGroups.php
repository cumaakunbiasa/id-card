<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;

class PermitGroups
{
    /** Keep legacy permits without a group_id separate, and isolate owners. */
    public static function query(Builder $query, ?string $reviewerRole = null): Builder
    {
        $query->selectRaw("MIN(work_permits.id) as id, work_permits.user_id, work_permits.group_id,
            MAX(company_name) as company_name, MAX(foreman_name) as foreman_name,
            COALESCE(MAX(CASE WHEN person_type = 'pekerja' THEN job_title END), MAX(job_title)) as job_title,
            MAX(work_location) as work_location, MAX(created_at) as created_at,
            COUNT(*) as people_count,
            SUM(CASE WHEN status = 'ditinjau' THEN 1 ELSE 0 END) as pending_people,
            SUM(CASE WHEN status = 'diizinkan' THEN 1 ELSE 0 END) as approved_people,
            SUM(CASE WHEN status = 'ditolak' THEN 1 ELSE 0 END) as rejected_people")
            ->groupBy('work_permits.user_id', 'work_permits.group_id')
            ->groupByRaw("CASE WHEN group_id IS NULL THEN work_permits.id ELSE NULL END");

        if ($reviewerRole !== null) {
            $query->selectRaw("SUM(CASE WHEN work_permits.status = 'ditinjau' AND EXISTS (
                SELECT 1 FROM permit_approvals
                WHERE permit_approvals.work_permit_id = work_permits.id
                  AND permit_approvals.admin_role = ? AND permit_approvals.status = 'menunggu'
            ) THEN 1 ELSE 0 END) as pending_for_role", [$reviewerRole]);
        }

        return $query;
    }
}
