<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccountChangeRequest;
use App\Models\ContestRecord;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Store;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'pendingStores' => Store::where('status', 'pending')->count(),
            'pendingContests' => ContestRecord::where('status', 'pending')->count(),
            'pendingPayments' => Payment::where('status', 'under_review')->count(),
            'pendingBankChanges' => BankAccountChangeRequest::where('status', 'pending')->count(),
            'completedOrders' => Order::where('status', 'completed')->count(),
            'recentStores' => Store::with('owner')->latest()->take(5)->get(),
        ]);
    }
}
