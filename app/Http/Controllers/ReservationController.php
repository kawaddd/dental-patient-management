<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    public function index(Request $request)
    {
        $sortCol = $request->input('sort', 'reserved_at');
        $sortDir = $request->input('dir', 'desc') === 'asc' ? 'asc' : 'desc';

        $query = Reservation::with(['customer', 'treatmentHistory']);

        // ソート
        if ($sortCol === 'customer') {
            $query->join('customers', 'reservations.customer_id', '=', 'customers.id')
                  ->orderBy('customers.name_kana', $sortDir)
                  ->select('reservations.*');
        } elseif ($sortCol === 'reservation_id') {
            $query->orderBy('reservation_id', $sortDir);
        } else {
            $query->orderBy('reserved_at', $sortDir);
        }

        // フィルター
        if ($statuses = $request->input('statuses')) {
            $query->whereIn('reservations.status', $statuses);
        }

        if ($date_from = $request->input('date_from')) {
            $query->whereDate('reserved_at', '>=', $date_from);
        }

        if ($date_to = $request->input('date_to')) {
            $query->whereDate('reserved_at', '<=', $date_to);
        }

        if ($search = $request->input('search')) {
            $query->whereHas('customer', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('name_kana', 'like', "%{$search}%")
                  ->orWhere('customer_id', 'like', "%{$search}%");
            });
        }

        if ($reservation_id = $request->input('reservation_id')) {
            $query->where('reservation_id', 'like', "%{$reservation_id}%");
        }

        if ($treatments = $request->input('treatment_types')) {
            $query->whereHas('treatmentHistory', function ($q) use ($treatments) {
                $q->whereIn('treatment_type', $treatments);
            });
        }

        $reservations = $query->paginate(25)->withQueryString();

        if ($request->wantsJson()) {
            return response()->json([
                'reservations' => $reservations->map(fn($r) => [
                    'id'             => $r->id,
                    'reservation_id' => $r->reservation_id,
                    'reserved_date'  => $r->reserved_at->format('Y/m/d'),
                    'reserved_time'  => $r->reserved_at->format('H:i'),
                    'status'         => $r->status,
                    'staff'          => $r->staff,
                    'customer'       => $r->customer ? [
                        'name'        => $r->customer->name,
                        'name_kana'   => $r->customer->name_kana ?? '',
                        'customer_id' => $r->customer->customer_id,
                        'url'         => route('customers.show', $r->customer),
                    ] : null,
                    'treatment_type' => $r->treatmentHistory?->treatment_type ?? '',
                    'treatment_area' => $r->treatmentHistory?->treatment_area ?? '',
                ]),
                'total'      => $reservations->total(),
                'pagination' => $reservations->links()->toHtml(),
            ]);
        }

        return view('reservations.index');
    }
}
