@extends('layouts.app')

@section('title', 'ダッシュボード')

@section('content')

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">ダッシュボード</h1>
    <p class="text-sm text-gray-500 mt-1">{{ now()->format('Y年m月d日 (D)') }}</p>
</div>

{{-- サマリーカード --}}
<div class="grid grid-cols-5 gap-5 mb-8">

    {{-- 患者総数 --}}
    <div class="col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <p class="text-sm font-medium text-gray-500">患者総数</p>
            <div class="w-9 h-9 rounded-xl bg-blue-50 flex items-center justify-center">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
        </div>
        <p class="text-3xl font-bold text-gray-900">{{ number_format($customerCount) }}</p>
        <p class="text-xs text-gray-400 mt-1">登録患者数</p>
    </div>

    {{-- 診療件数3カード --}}
    <div class="col-span-3 grid grid-cols-3 gap-4">

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex flex-col justify-between">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-medium text-gray-500">本日の診療</p>
                <div class="w-7 h-7 rounded-lg bg-blue-50 flex items-center justify-center">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>
            <div>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($todayCount) }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ now()->format('m/d') }}</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex flex-col justify-between">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-medium text-gray-500">直近7日</p>
                <div class="w-7 h-7 rounded-lg bg-purple-50 flex items-center justify-center">
                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
            </div>
            <div>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($week7Count) }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ now()->subDays(6)->format('m/d') }}〜{{ now()->format('m/d') }}</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex flex-col justify-between">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-medium text-gray-500">今月の診療</p>
                <div class="w-7 h-7 rounded-lg bg-green-50 flex items-center justify-center">
                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>
            <div>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($monthCount) }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ now()->format('Y年m月') }}</p>
            </div>
        </div>

    </div>

</div>

{{-- 最近の診療履歴 --}}
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <h2 class="font-semibold text-gray-800">最近の診療履歴</h2>
        <a href="{{ route('customers.index') }}" class="text-xs text-blue-600 hover:underline">患者一覧 →</a>
    </div>

    @forelse($recentHistories as $history)
        <a href="{{ route('customers.show', $history->customer) }}"
           class="flex items-center justify-between px-6 py-4 border-b border-gray-50 hover:bg-gray-50 transition-colors last:border-0">
            <div class="flex items-center gap-4">
                <div>
                    <p class="font-medium text-gray-900 text-sm">{{ $history->customer->name }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">
                        {{ $history->treatment_type }}
                        @if($history->treatment_area)
                            <span class="text-gray-400">・{{ $history->treatment_area }}</span>
                        @endif
                    </p>
                </div>
            </div>
            <div class="text-right">
                <p class="text-xs text-gray-400">{{ $history->treated_at->format('m/d') }}</p>
                @if($history->staff)
                    <p class="text-xs text-gray-400 mt-0.5">{{ $history->staff }}</p>
                @endif
            </div>
        </a>
    @empty
        <div class="px-6 py-12 text-center text-gray-400 text-sm">診療履歴がありません</div>
    @endforelse
</div>

@endsection
