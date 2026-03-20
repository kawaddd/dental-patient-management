<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $query = Customer::with('store')->withMax('treatmentHistories', 'treated_at');

        if ($request->filled('customer_id')) {
            $query->where('customer_id', 'like', "%{$request->customer_id}%");
        }

        if ($request->filled('store_ids')) {
            $query->whereIn('store_id', $request->store_ids);
        }

        if ($request->filled('treatment_types')) {
            $query->whereHas('treatmentHistories', fn($q) =>
                $q->whereIn('treatment_type', $request->treatment_types)
            );
        }

        if ($request->filled('treatment_areas')) {
            $query->whereHas('treatmentHistories', fn($q) =>
                $q->whereIn('treatment_area', $request->treatment_areas)
            );
        }

        $sortCol = $request->input('sort', '');
        $sortDir = $request->input('dir', 'asc') === 'desc' ? 'desc' : 'asc';

        match ($sortCol) {
            'name'       => $query->orderBy('name_kana', $sortDir),
            'last_visit' => $query->orderBy('treatment_histories_max_treated_at', $sortDir),
            default      => $query->orderBy('customer_id', $sortDir),
        };

        $customers = $query->paginate(15)->withQueryString();
        $stores    = Store::orderBy('store_code')->get();

        if ($request->wantsJson()) {
            return response()->json([
                'total'     => $customers->total(),
                'customers' => $customers->map(fn($c) => [
                    'id'                  => $c->id,
                    'name'                => $c->name,
                    'name_kana'           => $c->name_kana ?? '',
                    'customer_id'         => $c->customer_id,
                    'store_name'          => $c->store->name ?? '—',
                    'last_treatment_date' => $c->treatment_histories_max_treated_at
                                            ? \Carbon\Carbon::parse($c->treatment_histories_max_treated_at)->format('Y/m/d')
                                            : '—',
                    'url'                 => route('customers.show', $c),
                ]),
                'pagination' => $customers->links()->toHtml(),
            ]);
        }

        return view('customers.index', compact('customers', 'stores'));
    }

    public function show(Customer $customer, Request $request): View
    {
        $historyQuery = $customer->treatmentHistories()->with('reservation');

        if ($request->filled('type')) {
            $historyQuery->byType($request->type);
        }

        if ($request->filled('area')) {
            $historyQuery->byArea($request->area);
        }

        $histories = $historyQuery->orderByDesc('treated_at')->get();

        $types = $customer->treatmentHistories()
                          ->select('treatment_type')
                          ->distinct()
                          ->pluck('treatment_type');

        $areas = $customer->treatmentHistories()
                          ->whereNotNull('treatment_area')
                          ->select('treatment_area')
                          ->distinct()
                          ->pluck('treatment_area');

        return view('customers.show', compact('customer', 'histories', 'types', 'areas'));
    }
}
