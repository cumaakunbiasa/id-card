<?php

namespace App\Http\Controllers;

use App\Models\WorkPermit;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $role = $request->user()->role;
        abort_unless(in_array($role, ['pemohon', 'humas', 'k3', 'keamanan', 'operator'], true), 403);

        $query = WorkPermit::query();
        if ($role === 'pemohon') {
            $query->where('user_id', $request->user()->id);
        } elseif ($role === 'operator') {
            $query->where('status', 'diizinkan');
        }

        $counts = (clone $query)->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')->pluck('total', 'status');
        $pending = null;
        if (in_array($role, ['humas', 'k3', 'keamanan'], true)) {
            $pending = (clone $query)->where('status', 'ditinjau')
                ->whereHas('approvals', fn ($q) => $q->where('admin_role', $role)->where('status', 'menunggu'))
                ->count();
        }
        $groupCount = null;
        if ($role === 'operator') {
            $recentPermits = $query->latest()->orderByDesc('id')->limit(5)->get();
        } else {
            $groups = \App\Services\PermitGroups::query(clone $query);
            $groupCount = \Illuminate\Support\Facades\DB::query()->fromSub(clone $groups, 'permit_groups')->count();
            $recentPermits = $groups->orderByDesc('id')->limit(5)->get();
        }

        return view('dashboards.'.$role, compact('counts', 'pending', 'recentPermits', 'groupCount'));
    }
}
