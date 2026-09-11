<?php

namespace App\Http\Controllers;

use App\Models\Coin;
use App\Models\Order;
use App\Models\Signal;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    /**
     * Display full order history journal table.
     */
    public function index(Request $request)
    {
        $selectedExchange  = $request->get('exchange', 'all');
        $selectedDirection = $request->get('direction', 'all');
        $selectedStatus    = $request->get('status', 'all');

        $query = Order::with(['signal', 'coin'])->latest('created_at');

        if ($selectedExchange !== 'all') {
            $query->where('exchange', 'like', '%' . $selectedExchange . '%');
        }

        if ($selectedDirection !== 'all') {
            $query->where('direction', $selectedDirection);
        }

        if ($selectedStatus !== 'all') {
            $query->where('status', $selectedStatus);
        }

        $orders = $query->paginate(25);

        // Stats summary
        $totalOrders   = Order::count();
        $totalMargin   = Order::sum('margin_usd');
        $totalPosition = Order::sum('position_size_usd');
        $binanceCount  = Order::where('exchange', 'like', '%Binance%')->count();
        $bybitCount    = Order::where('exchange', 'like', '%Bybit%')->count();

        return view('orders.index', compact(
            'orders',
            'totalOrders',
            'totalMargin',
            'totalPosition',
            'binanceCount',
            'bybitCount',
            'selectedExchange',
            'selectedDirection',
            'selectedStatus'
        ));
    }

    /**
     * Store new executed order via AJAX / One-Click Console.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'signal_id'         => ['nullable', 'exists:signals,id'],
            'symbol'            => ['required', 'string'],
            'direction'         => ['required', 'in:LONG,SHORT'],
            'exchange'          => ['required', 'string'],
            'order_type'        => ['required', 'in:LIMIT,MARKET'],
            'entry_price'       => ['required', 'numeric'],
            'stop_loss'         => ['required', 'numeric'],
            'take_profit'       => ['nullable', 'numeric'],
            'margin_usd'        => ['nullable', 'numeric'],
            'position_size_usd' => ['nullable', 'numeric'],
            'leverage'          => ['nullable', 'integer'],
        ]);

        $signal = isset($validated['signal_id']) ? Signal::find($validated['signal_id']) : null;
        $coinId = $signal ? $signal->coin_id : optional(Coin::where('symbol', strtoupper($validated['symbol']))->first())->id;

        $ticketId = '#' . rand(100000, 999999);

        $order = Order::create([
            'ticket_id'         => $ticketId,
            'signal_id'         => $validated['signal_id'] ?? null,
            'coin_id'           => $coinId,
            'symbol'            => strtoupper($validated['symbol']),
            'direction'         => $validated['direction'],
            'exchange'          => $validated['exchange'],
            'order_type'        => $validated['order_type'],
            'entry_price'       => $validated['entry_price'],
            'stop_loss'         => $validated['stop_loss'],
            'take_profit'       => $validated['take_profit'] ?? null,
            'margin_usd'        => $validated['margin_usd'] ?? 0,
            'position_size_usd' => $validated['position_size_usd'] ?? 0,
            'leverage'          => $validated['leverage'] ?? 10,
            'status'            => 'EXECUTED',
            'outcome'           => 'PENDING',
        ]);

        return response()->json([
            'success'   => true,
            'message'   => 'Order recorded successfully to execution history table!',
            'order'     => $order,
            'ticket_id' => $ticketId,
            'datetime'  => $order->created_at->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Update order status manually.
     */
    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:EXECUTED,FILLED,CLOSED,STOPPED,CANCELLED'],
        ]);

        $order->update(['status' => $validated['status']]);

        return back()->with('success', "Order {$order->ticket_id} status updated to {$order->status}.");
    }

    /**
     * Update order exact outcome target marker manually.
     */
    public function updateOutcome(Request $request, Order $order)
    {
        $validated = $request->validate([
            'outcome' => ['required', 'in:PENDING,FILLED,HIT_TP1,HIT_TP2,HIT_TP3,HIT_BE,HIT_SL,CANCELLED'],
        ]);

        $outcome = $validated['outcome'];
        $status  = match(true) {
            str_contains($outcome, 'HIT_TP') || $outcome === 'HIT_BE' => 'CLOSED',
            $outcome === 'HIT_SL' => 'STOPPED',
            $outcome === 'CANCELLED' => 'CANCELLED',
            $outcome === 'FILLED' => 'FILLED',
            default => 'EXECUTED',
        };

        $order->update([
            'outcome' => $outcome,
            'status'  => $status,
        ]);

        return back()->with('success', "Order {$order->ticket_id} outcome marker updated to {$outcome}.");
    }

    /**
     * Delete an order record.
     */
    public function destroy(Order $order)
    {
        $order->delete();
        return back()->with('success', 'Order log deleted successfully.');
    }
}
