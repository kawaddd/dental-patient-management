@extends('layouts.app')

@section('title', $customer->name . ' - 患者詳細')

@section('content')

{{-- ヘッダー --}}
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('customers.index') }}"
       class="flex items-center justify-center w-9 h-9 rounded-xl bg-white border border-gray-200 hover:bg-gray-50 transition-colors shrink-0">
        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
    </a>
    <div>
        <h1 class="text-2xl font-bold text-gray-900">
            {{ $customer->name }}
            @if($customer->name_kana)
                <span class="text-lg font-normal text-gray-400 ml-1">（{{ $customer->name_kana }}）</span>
            @endif
        </h1>
        <p class="text-sm text-gray-400">患者ID: {{ $customer->customer_id }}</p>
    </div>
</div>

{{-- 2カラムレイアウト --}}
<div class="flex flex-col lg:flex-row gap-6 items-start">

    {{-- 左カラム: 基本情報 --}}
    <div class="w-full lg:w-72 lg:shrink-0 space-y-4">

        {{-- 患者情報カード --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">

            {{-- カードヘッダー --}}
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 px-5 py-5">
                <p class="text-white font-bold text-lg leading-tight">{{ $customer->name }}</p>
                @if($customer->name_kana)
                    <p class="text-blue-100 text-sm mt-0.5">{{ $customer->name_kana }}</p>
                @endif
                <p class="text-blue-200 text-xs mt-1">{{ $customer->customer_id }}</p>
            </div>

            {{-- 詳細情報 --}}
            <div class="px-5 py-4 space-y-3">
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm px-4 py-3 space-y-3">

                    <div class="flex items-start justify-between gap-2">
                        <span class="text-xs text-gray-400 shrink-0 mt-0.5">院</span>
                        <span class="text-sm font-semibold text-gray-800 text-right">{{ $customer->store->name ?? '—' }}</span>
                    </div>

                    <div class="h-px bg-gray-50"></div>

                    <div class="flex items-start justify-between gap-2">
                        <span class="text-xs text-gray-400 shrink-0 mt-0.5">生年月日</span>
                        <div class="text-right">
                            @if($customer->birth_date)
                                <p class="text-sm font-semibold text-gray-800">{{ $customer->birth_date->format('Y/m/d') }}</p>
                                <p class="text-xs text-gray-400">{{ $customer->birth_date->age }}歳</p>
                            @else
                                <span class="text-sm text-gray-400">—</span>
                            @endif
                        </div>
                    </div>

                    <div class="h-px bg-gray-50"></div>

                    <div class="flex items-start justify-between gap-2">
                        <span class="text-xs text-gray-400 shrink-0 mt-0.5">性別</span>
                        <span class="text-sm font-semibold text-gray-800">
                            {{ ['male' => '男性', 'female' => '女性', 'other' => 'その他'][$customer->gender] ?? '—' }}
                        </span>
                    </div>

                    @if($customer->phone)
                    <div class="h-px bg-gray-50"></div>
                    <div class="flex items-start justify-between gap-2">
                        <span class="text-xs text-gray-400 shrink-0 mt-0.5">電話番号</span>
                        <span class="text-sm text-gray-800">{{ $customer->phone }}</span>
                    </div>
                    @endif

                    @if($customer->email)
                    <div class="h-px bg-gray-50"></div>
                    <div class="flex items-start justify-between gap-2">
                        <span class="text-xs text-gray-400 shrink-0 mt-0.5">メール</span>
                        <span class="text-sm text-gray-800 break-all text-right">{{ $customer->email }}</span>
                    </div>
                    @endif

                </div>

                @if($customer->notes)
                <div class="bg-amber-50 border border-amber-100 rounded-xl px-4 py-3">
                    <p class="text-xs font-semibold text-amber-600 mb-1">備考</p>
                    <p class="text-sm text-amber-800 leading-relaxed">{{ $customer->notes }}</p>
                </div>
                @endif

            </div>
        </div>

        {{-- 診療統計 --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm px-5 py-4">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">診療実績</p>
            <div class="flex items-end gap-1">
                <span class="text-3xl font-bold text-gray-900">{{ $customer->treatmentHistories()->count() }}</span>
                <span class="text-sm text-gray-400 mb-1">件</span>
            </div>
            <p class="text-xs text-gray-400 mt-1">累計診療回数</p>
        </div>

    </div>

    {{-- 右カラム: 診療履歴タイムライン --}}
    <div class="flex-1 min-w-0">

        {{-- フィルタバー --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm px-5 py-4 mb-5 space-y-3">

            {{-- 治療内容フィルタ --}}
            <div class="flex items-start gap-3">
                <p class="text-xs font-semibold text-gray-400 w-16 pt-1.5 shrink-0">治療内容</p>
                <div class="flex gap-1.5 flex-wrap">
                    <a href="{{ route('customers.show', array_merge(request()->except('type'), ['customer' => $customer->id])) }}"
                       class="px-3 py-1.5 rounded-lg text-xs font-medium border transition-colors
                              {{ !request('type') ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-200 hover:border-blue-300 hover:text-blue-600' }}">
                        すべて
                    </a>
                    @foreach($types as $type)
                        <a href="{{ route('customers.show', array_merge(request()->all(), ['customer' => $customer->id, 'type' => $type])) }}"
                           class="px-3 py-1.5 rounded-lg text-xs font-medium border transition-colors
                                  {{ request('type') === $type ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-200 hover:border-blue-300 hover:text-blue-600' }}">
                            {{ $type }}
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- 治療部位フィルタ --}}
            @if($areas->isNotEmpty())
            <div class="flex items-start gap-3 pt-3 border-t border-gray-50">
                <p class="text-xs font-semibold text-gray-400 w-16 pt-1.5 shrink-0">治療部位</p>
                <div class="flex gap-1.5 flex-wrap">
                    <a href="{{ route('customers.show', array_merge(request()->except('area'), ['customer' => $customer->id])) }}"
                       class="px-3 py-1.5 rounded-lg text-xs font-medium border transition-colors
                              {{ !request('area') ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-200 hover:border-blue-300 hover:text-blue-600' }}">
                        すべて
                    </a>
                    @foreach($areas as $area)
                        <a href="{{ route('customers.show', array_merge(request()->all(), ['customer' => $customer->id, 'area' => $area])) }}"
                           class="px-3 py-1.5 rounded-lg text-xs font-medium border transition-colors
                                  {{ request('area') === $area ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-200 hover:border-blue-300 hover:text-blue-600' }}">
                            {{ $area }}
                        </a>
                    @endforeach
                </div>
            </div>
            @endif

        </div>

        {{-- タイムライン --}}
        <div class="space-y-0">
            @forelse($histories as $history)
            <div class="flex gap-4">

                {{-- タイムライン軸 --}}
                <div class="flex flex-col items-center pt-5 w-5 shrink-0">
                    <div class="w-3 h-3 rounded-full bg-blue-500 ring-2 ring-blue-100 shrink-0"></div>
                    @unless($loop->last)
                        <div class="w-px flex-1 bg-gray-200 mt-1.5 mb-0"></div>
                    @endunless
                </div>

                {{-- カード --}}
                <div class="flex-1 bg-white rounded-2xl border border-gray-100 shadow-sm p-5 mb-3">
                    <div class="flex items-start justify-between gap-4">

                        {{-- 左: 治療内容 --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                                <p class="font-semibold text-gray-900">{{ $history->treatment_type }}</p>
                                <x-badge :status="$history->reservation?->status ?? 'completed'" />
                            </div>
                            @if($history->treatment_area)
                                <span class="inline-block text-xs font-medium text-blue-700 bg-blue-50 px-2.5 py-1 rounded-lg mb-2">
                                    {{ $history->treatment_area }}
                                </span>
                            @endif
                            @if($history->notes)
                                <p class="text-sm text-gray-500 leading-relaxed">{{ $history->notes }}</p>
                            @endif
                        </div>

                        {{-- 右: 日付・担当 --}}
                        <div class="text-right shrink-0">
                            <p class="text-sm font-semibold text-gray-700">{{ $history->treated_at->format('Y/m/d') }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">{{ $history->treated_at->format('(D)') }}</p>
                            @if($history->staff)
                                <p class="text-xs text-gray-400 mt-1.5 flex items-center justify-end gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    {{ $history->staff }}
                                </p>
                            @endif
                        </div>

                    </div>
                </div>
            </div>
            @empty
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm px-6 py-16 text-center">
                    <svg class="w-10 h-10 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <p class="text-gray-400 text-sm">診療履歴はありません</p>
                </div>
            @endforelse
        </div>

    </div>
</div>

@endsection
