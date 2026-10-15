<?php

namespace App\Http\Controllers\landlord;

use App\Http\Controllers\Controller;
use App\Models\landlord\Tenant;
use App\Models\landlord\TenantPayment;
use App\Models\landlord\Package;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $totalTenants    = Tenant::count();
        $activeTenants   = Tenant::active()->count();
        $expiredTenants  = Tenant::expired()->count();
        $trialTenants    = Tenant::where('subscription_type', 'free_trial')->count();
        $expiringSoon    = Tenant::expiringSoon(7)->count();

        // Revenue
        $mrr = TenantPayment::where('status', 'completed')
            ->where('subscription_type', 'monthly')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        $arr = TenantPayment::where('status', 'completed')
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        // Monthly signups for chart (last 12 months)
        $signupChart = Tenant::selectRaw('MONTH(created_at) as month, YEAR(created_at) as year, COUNT(*) as count')
            ->where('created_at', '>=', now()->subMonths(11)->startOfMonth())
            ->groupByRaw('YEAR(created_at), MONTH(created_at)')
            ->orderByRaw('YEAR(created_at), MONTH(created_at)')
            ->get();

        // Revenue chart (last 12 months)
        $revenueChart = TenantPayment::selectRaw('MONTH(created_at) as month, YEAR(created_at) as year, SUM(amount) as total')
            ->where('status', 'completed')
            ->where('created_at', '>=', now()->subMonths(11)->startOfMonth())
            ->groupByRaw('YEAR(created_at), MONTH(created_at)')
            ->orderByRaw('YEAR(created_at), MONTH(created_at)')
            ->get();

        $recentTenants   = Tenant::with('package')->latest()->limit(10)->get();
        $recentPayments  = TenantPayment::with('tenant')->latest()->limit(10)->get();

        return view('superadmin.dashboard', compact(
            'totalTenants', 'activeTenants', 'expiredTenants', 'trialTenants',
            'expiringSoon', 'mrr', 'arr', 'signupChart', 'revenueChart',
            'recentTenants', 'recentPayments'
        ));
    }
}
