<?php

namespace App\Http\Controllers;

use App\Models\Finance;
use App\Models\Shipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FinanceController extends Controller
{
    public function index(Request $request)
    {
        $query = Finance::where('user_id', Auth::id());

        // Filter by type
        if ($request->has('type') && $request->type != '') {
            $query->where('type', $request->type);
        }

        // Filter by date range
        if ($request->has('date_from') && $request->date_from != '') {
            $query->whereDate('transaction_date', '>=', $request->date_from);
        }
        if ($request->has('date_to') && $request->date_to != '') {
            $query->whereDate('transaction_date', '<=', $request->date_to);
        }

        $finances = $query->latest('transaction_date')->paginate(10);

        // Summary
        $totalIncome = Finance::where('user_id', Auth::id())
                             ->where('type', 'income')
                             ->sum('amount');
        
        $totalExpense = Finance::where('user_id', Auth::id())
                              ->where('type', 'expense')
                              ->sum('amount');

        $balance = $totalIncome - $totalExpense;

        // Monthly data for chart
        $monthlyData = Finance::where('user_id', Auth::id())
                             ->select(
                                 DB::raw('MONTH(transaction_date) as month'),
                                 DB::raw('SUM(CASE WHEN type = "income" THEN amount ELSE 0 END) as income'),
                                 DB::raw('SUM(CASE WHEN type = "expense" THEN amount ELSE 0 END) as expense')
                             )
                             ->whereYear('transaction_date', date('Y'))
                             ->groupBy('month')
                             ->orderBy('month')
                             ->get();

        return view('finances.index', compact('finances', 'totalIncome', 'totalExpense', 'balance', 'monthlyData'));
    }

    public function create()
    {
        $shipments = Shipment::where('user_id', Auth::id())->latest()->get();
        return view('finances.create', compact('shipments'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:income,expense',
            'category' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'customer_name' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,transfer,card,e-wallet',
            'transaction_date' => 'required|date',
            'shipment_id' => 'nullable|exists:shipments,id',
            'notes' => 'nullable|string',
        ]);

        $validated['user_id'] = Auth::id();

        Finance::create($validated);

        return redirect()->route('finances.index')
                        ->with('success', 'Transaksi berhasil ditambahkan!');
    }

    public function show(Finance $finance)
    {
        if ($finance->user_id !== Auth::id()) {
            abort(403);
        }

        return view('finances.show', compact('finance'));
    }

    public function edit(Finance $finance)
    {
        if ($finance->user_id !== Auth::id()) {
            abort(403);
        }

        $shipments = Shipment::where('user_id', Auth::id())->latest()->get();
        return view('finances.edit', compact('finance', 'shipments'));
    }

    public function update(Request $request, Finance $finance)
    {
        if ($finance->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'type' => 'required|in:income,expense',
            'category' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'customer_name' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,transfer,card,e-wallet',
            'transaction_date' => 'required|date',
            'shipment_id' => 'nullable|exists:shipments,id',
            'notes' => 'nullable|string',
        ]);

        $finance->update($validated);

        return redirect()->route('finances.index')
                        ->with('success', 'Transaksi berhasil diupdate!');
    }

    public function destroy(Finance $finance)
    {
        if ($finance->user_id !== Auth::id()) {
            abort(403);
        }

        $finance->delete();

        return redirect()->route('finances.index')
                        ->with('success', 'Transaksi berhasil dihapus!');
    }
}