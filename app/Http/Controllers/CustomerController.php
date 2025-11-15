<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Shipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::where('user_id', Auth::id());

        // Search
        if ($request->has('search') && $request->search != '') {
            $query->where(function($q) use ($request) {
                $q->where('name', 'LIKE', '%' . $request->search . '%')
                  ->orWhere('phone', 'LIKE', '%' . $request->search . '%')
                  ->orWhere('email', 'LIKE', '%' . $request->search . '%');
            });
        }

        // Filter by type
        if ($request->has('type') && $request->type != '') {
            $query->where('customer_type', $request->type);
        }

        $customers = $query->latest()->paginate(10);

        // Stats
        $totalCustomers = Customer::where('user_id', Auth::id())->count();
        $individualCount = Customer::where('user_id', Auth::id())->where('customer_type', 'individual')->count();
        $companyCount = Customer::where('user_id', Auth::id())->where('customer_type', 'company')->count();

        return view('customers.index', compact('customers', 'totalCustomers', 'individualCount', 'companyCount'));
    }

    public function create()
    {
        return view('customers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
            'city' => 'nullable|string|max:255',
            'customer_type' => 'required|in:individual,company',
            'company_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $validated['user_id'] = Auth::id();

        Customer::create($validated);

        return redirect()->route('customers.index')
                        ->with('success', 'Pelanggan berhasil ditambahkan!');
    }

    public function show(Customer $customer)
    {
        if ($customer->user_id !== Auth::id()) {
            abort(403);
        }

        // Get shipment history
        $shipments = Shipment::where('user_id', Auth::id())
                            ->where('receiver_name', $customer->name)
                            ->latest()
                            ->take(10)
                            ->get();

        $totalShipments = Shipment::where('user_id', Auth::id())
                                 ->where('receiver_name', $customer->name)
                                 ->count();

        $totalSpent = Shipment::where('user_id', Auth::id())
                             ->where('receiver_name', $customer->name)
                             ->sum('cost');

        return view('customers.show', compact('customer', 'shipments', 'totalShipments', 'totalSpent'));
    }

    public function edit(Customer $customer)
    {
        if ($customer->user_id !== Auth::id()) {
            abort(403);
        }

        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        if ($customer->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
            'city' => 'nullable|string|max:255',
            'customer_type' => 'required|in:individual,company',
            'company_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $customer->update($validated);

        return redirect()->route('customers.index')
                        ->with('success', 'Data pelanggan berhasil diupdate!');
    }

    public function destroy(Customer $customer)
    {
        if ($customer->user_id !== Auth::id()) {
            abort(403);
        }

        $customer->delete();

        return redirect()->route('customers.index')
                        ->with('success', 'Pelanggan berhasil dihapus!');
    }
}