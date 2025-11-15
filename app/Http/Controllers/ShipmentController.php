<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use App\Models\Finance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShipmentController extends Controller
{
    public function index()
    {
        $shipments = Shipment::where('user_id', Auth::id())
                            ->latest()
                            ->paginate(10);
        
        return view('shipments.index', compact('shipments'));
    }

    public function create()
    {
        return view('shipments.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sender_name' => 'required|string|max:255',
            'sender_phone' => 'required|string|max:20',
            'sender_address' => 'required|string',
            'receiver_name' => 'required|string|max:255',
            'receiver_phone' => 'required|string|max:20',
            'receiver_address' => 'required|string',
            'origin' => 'required|string|max:255',
            'destination' => 'required|string|max:255',
            'weight' => 'required|numeric|min:0',
            'package_type' => 'required|string',
            'cost' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'estimated_delivery' => 'nullable|date',
        ]);

        $validated['user_id'] = Auth::id();
        $validated['status'] = 'pending';

        // Create Shipment
        $shipment = Shipment::create($validated);

        // Auto Sync ke Keuangan - Create Income Transaction
        Finance::create([
              'user_id' => Auth::id(),
    'shipment_id' => $shipment->id,
    'type' => 'income',
    'category' => 'Pengiriman',
    'description' => 'Biaya pengiriman ' . $shipment->tracking_number . ' (' . $shipment->origin . ' → ' . $shipment->destination . ')',
    'customer_name' => $shipment->receiver_name,  // ← TAMBAHKAN INI
    'amount' => $shipment->cost,
    'payment_method' => 'cash',
    'transaction_date' => now(),
        ]);

        return redirect()->route('shipments.index')
                        ->with('success', 'Pengiriman berhasil dibuat dan transaksi keuangan telah dicatat!');
    }

    public function show(Shipment $shipment)
    {
        if ($shipment->user_id !== Auth::id()) {
            abort(403);
        }

        return view('shipments.show', compact('shipment'));
    }

    public function edit(Shipment $shipment)
    {
        if ($shipment->user_id !== Auth::id()) {
            abort(403);
        }

        return view('shipments.edit', compact('shipment'));
    }

    public function update(Request $request, Shipment $shipment)
    {
        if ($shipment->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'sender_name' => 'required|string|max:255',
            'sender_phone' => 'required|string|max:20',
            'sender_address' => 'required|string',
            'receiver_name' => 'required|string|max:255',
            'receiver_phone' => 'required|string|max:20',
            'receiver_address' => 'required|string',
            'origin' => 'required|string|max:255',
            'destination' => 'required|string|max:255',
            'weight' => 'required|numeric|min:0',
            'package_type' => 'required|string',
            'status' => 'required|in:pending,processing,in_transit,delivered,cancelled',
            'cost' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'estimated_delivery' => 'nullable|date',
            'actual_delivery' => 'nullable|date',
        ]);

        // Simpan biaya lama untuk cek perubahan
        $oldCost = $shipment->cost;

        // Update Shipment
        $shipment->update($validated);

        // Update transaksi keuangan jika biaya berubah
        if ($oldCost != $validated['cost']) {
            $finance = Finance::where('shipment_id', $shipment->id)->first();
            if ($finance) {
                $finance->update([
                    'amount' => $shipment->cost,
                    'description' => 'Biaya pengiriman ' . $shipment->tracking_number . ' (' . $shipment->origin . ' → ' . $shipment->destination . ')',
                ]);
            }
        }

        return redirect()->route('shipments.index')
                        ->with('success', 'Pengiriman berhasil diupdate!');
    }

    public function destroy(Shipment $shipment)
    {
        if ($shipment->user_id !== Auth::id()) {
            abort(403);
        }

        // Hapus transaksi keuangan terkait terlebih dahulu
        Finance::where('shipment_id', $shipment->id)->delete();

        // Hapus shipment
        $shipment->delete();

        return redirect()->route('shipments.index')
                        ->with('success', 'Pengiriman dan transaksi keuangan berhasil dihapus!');
    }
}