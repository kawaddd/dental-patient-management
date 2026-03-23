@extends('layouts.app')

@section('title', '予約一覧')

@section('content')

<div x-data="reservationSearch()" x-init="init()" @keydown.window.escape="clearFilters()">

{{-- ページヘッダー --}}
<div class="flex items-center justify-between mb-3">
    <h1 class="text-2xl font-bold text-gray-900">予約一覧</h1>
    <div class="flex items-center gap-2">
        <button x-show="activeFilterCount > 0"
                x-cloak
                @click="clearFilters()"
                class="text-xs text-gray-400 hover:text-gray-600 px-2 py-2 transition-colors">
            すべてクリア
        </button>
        <button @click="showFilters = !showFilters"
                class="flex items-center gap-1.5 px-3 py-2 bg-white border border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors min-h-[40px]">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
            </svg>
            絞り込み
            <span x-show="filterCount > 0"
                  class="bg-blue-600 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center font-bold leading-none"
                  x-text="filterCount"></span>
        </button>
    </div>
</div>

{{-- 検索ボックス（常時表示）--}}
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm px-3 py-3 mb-3">
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2">

        <div>
            <label class="block text-xs text-gray-400 mb-1">予約ID</label>
            <input type="text" x-model="filters.reservation_id" @input.debounce.400ms="search()"
                   class="w-full border border-gray-200 rounded-lg px-2.5 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-50 focus:bg-white transition-all"
                   placeholder="R001">
        </div>

        <div>
            <label class="block text-xs text-gray-400 mb-1">患者 / 患者（カナ）</label>
            <input type="text" x-model="filters.search" @input.debounce.400ms="search()"
                   class="w-full border border-gray-200 rounded-lg px-2.5 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-50 focus:bg-white transition-all"
                   placeholder="山田 / ヤマダ">
        </div>

        <div>
            <label class="block text-xs text-gray-400 mb-1">予約日時（開始）</label>
            <input type="date" x-model="filters.date_from"
                   :max="filters.date_to || ''"
                   @change="if (filters.date_to && $event.target.value > filters.date_to) { filters.date_to = '' } search()"
                   class="w-full border border-gray-200 rounded-lg px-2.5 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-50 focus:bg-white transition-all">
        </div>

        <div>
            <label class="block text-xs text-gray-400 mb-1">予約日時（終了）</label>
            <input type="date" x-model="filters.date_to"
                   :min="filters.date_from || ''"
                   @change="if (filters.date_from && $event.target.value < filters.date_from) { filters.date_from = '' } search()"
                   class="w-full border border-gray-200 rounded-lg px-2.5 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-50 focus:bg-white transition-all">
        </div>

    </div>
</div>

{{-- フィルターパネル（トグル）--}}
<div x-show="showFilters"
     x-cloak
     x-transition:enter="transition ease-out duration-150"
     x-transition:enter-start="opacity-0 -translate-y-1"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-100"
     x-transition:leave-start="opacity-100 translate-y-0"
     x-transition:leave-end="opacity-0 -translate-y-1"
     class="bg-white rounded-2xl border border-gray-100 shadow-sm px-3 py-3 mb-3">
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">

        {{-- ステータス --}}
        <div>
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5">
                ステータス
                <span x-show="filters.statuses.length > 0"
                      class="ml-1 bg-blue-100 text-blue-700 text-xs px-1.5 py-0.5 rounded-full"
                      x-text="filters.statuses.length"></span>
            </p>
            <div class="space-y-1">
                @foreach(['reserved' => '予約中', 'completed' => '完了', 'cancelled' => 'キャンセル'] as $val => $label)
                <label class="flex items-center gap-2 cursor-pointer group">
                    <input type="checkbox" value="{{ $val }}"
                           x-model="filters.statuses" @change="search()"
                           class="w-3.5 h-3.5 rounded text-blue-600 border-gray-300">
                    <span class="text-sm text-gray-700 group-hover:text-gray-900">{{ $label }}</span>
                </label>
                @endforeach
            </div>
        </div>

        {{-- 治療内容（2カラム展開）--}}
        <div class="sm:col-span-2">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5">
                治療内容
                <span x-show="filters.treatment_types.length > 0"
                      class="ml-1 bg-blue-100 text-blue-700 text-xs px-1.5 py-0.5 rounded-full"
                      x-text="filters.treatment_types.length"></span>
            </p>
            <div class="max-h-44 overflow-y-auto space-y-0 pr-1">
                @foreach([
                    '検査・診断'     => ['初診検査','定期健診','精密検査','レントゲン撮影','CT撮影','型取り（印象採得）','咬合検査'],
                    '一般歯科'       => ['虫歯治療','レジン充填（白い詰め物）','インレー（金属詰め物）','セラミックインレー','根管治療（神経治療）','抜髄（神経を抜く）','感染根管治療（再治療）','歯根端切除術','抜歯','親知らず抜歯','乳歯治療'],
                    '補綴'           => ['クラウン（被せ物）','メタルクラウン','セラミッククラウン','ジルコニアクラウン','ブリッジ','部分入れ歯（局部義歯）','総入れ歯（全部床義歯）','義歯調整','仮歯（テンポラリークラウン）'],
                    '歯周病・予防'   => ['歯石除去（スケーリング）','スケーリング・ルートプレーニング（SRP）','歯周ポケット掻爬（キュレタージ）','歯周外科治療','クリーニング（PMTC）','フッ素塗布','シーラント（溝埋め）','歯周病治療','歯肉切除術（歯肉炎治療）'],
                    'インプラント'   => ['インプラント埋入','インプラント二次手術','インプラント上部構造装着','インプラントメンテナンス','骨造成（GBR）','サイナスリフト'],
                    '矯正'           => ['ワイヤー矯正','マウスピース矯正','部分矯正','保定装置（リテーナー）装着','矯正装置調整'],
                    '審美'           => ['オフィスホワイトニング','ホームホワイトニング','ラミネートベニア'],
                    'その他'         => ['口腔外科処置','顎関節症治療','ナイトガード（マウスピース）製作','縫合・抜糸','薬剤処置','応急処置'],
                ] as $group => $items)
                <div x-data="{ open: false }">
                    <button @click="open = !open"
                            class="flex items-center justify-between w-full text-xs font-semibold text-gray-400 py-1 hover:text-gray-600 transition-colors">
                        <span>{{ $group }}</span>
                        <svg class="w-3 h-3 shrink-0 transition-transform duration-150" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open" x-collapse>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-x-3 gap-y-0 pb-1">
                            @foreach($items as $item)
                            <label class="flex items-center gap-1.5 cursor-pointer group py-0.5">
                                <input type="checkbox" value="{{ $item }}"
                                       x-model="filters.treatment_types" @change="search()"
                                       class="w-3.5 h-3.5 rounded text-blue-600 border-gray-300 shrink-0">
                                <span class="text-xs text-gray-600 group-hover:text-gray-900 leading-tight truncate">{{ $item }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

    </div>
</div>

{{-- テーブルエリア --}}
<div>

    <div class="flex items-center justify-between mb-2">
        <p class="text-sm text-gray-500">
            <span class="font-semibold text-gray-800" x-text="total"></span> 件
        </p>
        <div x-show="loading" class="flex items-center gap-1.5 text-xs text-gray-400">
            <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            検索中
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-x-auto">
        <table class="w-full text-sm" style="table-layout: fixed; min-width: 640px;">
            <colgroup>
                <col style="width: 14%"> {{-- 予約日時 --}}
                <col style="width: 10%"> {{-- 予約ID --}}
                <col style="width: 24%"> {{-- 患者 --}}
                <col style="width: 13%"> {{-- ステータス --}}
                <col style="width: 27%"> {{-- 治療内容 --}}
                <col style="width: 12%"> {{-- 担当者 --}}
            </colgroup>
            <thead>
                <tr class="border-b border-gray-100 bg-gray-50">

                    <th class="px-4 py-3 text-left">
                        <div class="flex items-center gap-1.5">
                            <span class="text-xs font-semibold uppercase tracking-wide"
                                  :class="sort.column === 'reserved_at' ? 'text-blue-600' : 'text-gray-400'">予約日時</span>
                            <div class="flex flex-col gap-px">
                                <button @click="setSort('reserved_at', 'asc')" :class="sort.column === 'reserved_at' && sort.dir === 'asc' ? 'text-blue-600' : 'text-gray-300 hover:text-gray-500'">
                                    <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 10 6"><path d="M5 0l5 6H0z"/></svg>
                                </button>
                                <button @click="setSort('reserved_at', 'desc')" :class="sort.column === 'reserved_at' && sort.dir === 'desc' ? 'text-blue-600' : 'text-gray-300 hover:text-gray-500'">
                                    <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 10 6"><path d="M5 6L0 0h10z"/></svg>
                                </button>
                            </div>
                        </div>
                    </th>

                    <th class="px-4 py-3 text-left">
                        <div class="flex items-center gap-1.5">
                            <span class="text-xs font-semibold uppercase tracking-wide"
                                  :class="sort.column === 'reservation_id' ? 'text-blue-600' : 'text-gray-400'">予約ID</span>
                            <div class="flex flex-col gap-px">
                                <button @click="setSort('reservation_id', 'asc')" :class="sort.column === 'reservation_id' && sort.dir === 'asc' ? 'text-blue-600' : 'text-gray-300 hover:text-gray-500'">
                                    <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 10 6"><path d="M5 0l5 6H0z"/></svg>
                                </button>
                                <button @click="setSort('reservation_id', 'desc')" :class="sort.column === 'reservation_id' && sort.dir === 'desc' ? 'text-blue-600' : 'text-gray-300 hover:text-gray-500'">
                                    <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 10 6"><path d="M5 6L0 0h10z"/></svg>
                                </button>
                            </div>
                        </div>
                    </th>

                    <th class="px-4 py-3 text-left">
                        <div class="flex items-center gap-1.5">
                            <span class="text-xs font-semibold uppercase tracking-wide"
                                  :class="sort.column === 'customer' ? 'text-blue-600' : 'text-gray-400'">患者</span>
                            <div class="flex flex-col gap-px">
                                <button @click="setSort('customer', 'asc')" :class="sort.column === 'customer' && sort.dir === 'asc' ? 'text-blue-600' : 'text-gray-300 hover:text-gray-500'">
                                    <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 10 6"><path d="M5 0l5 6H0z"/></svg>
                                </button>
                                <button @click="setSort('customer', 'desc')" :class="sort.column === 'customer' && sort.dir === 'desc' ? 'text-blue-600' : 'text-gray-300 hover:text-gray-500'">
                                    <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 10 6"><path d="M5 6L0 0h10z"/></svg>
                                </button>
                            </div>
                        </div>
                    </th>

                    <th class="px-4 py-3 text-left">
                        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">ステータス</span>
                    </th>

                    <th class="px-4 py-3 text-left">
                        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">治療内容</span>
                    </th>

                    <th class="px-4 py-3 text-left">
                        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">担当者</span>
                    </th>

                </tr>
            </thead>
            <tbody>
                <template x-if="loading">
                    <tr><td colspan="6" class="px-6 py-14 text-center">
                        <svg class="w-6 h-6 animate-spin text-blue-400 mx-auto" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </td></tr>
                </template>

                <template x-if="!loading && reservations.length === 0">
                    <tr><td colspan="6" class="px-6 py-16 text-center text-gray-400 text-sm">
                        該当する予約データがありません
                    </td></tr>
                </template>

                <template x-for="r in reservations" :key="r.id">
                    <tr class="border-b border-gray-50 last:border-0 hover:bg-blue-50 transition-colors">

                        <td class="px-4 py-3.5 overflow-hidden">
                            <div class="font-medium text-gray-800 text-sm" x-text="r.reserved_date"></div>
                            <div class="text-xs text-gray-400" x-text="r.reserved_time"></div>
                        </td>

                        <td class="px-4 py-3.5 overflow-hidden text-xs text-gray-400 font-mono truncate" x-text="r.reservation_id"></td>

                        <td class="px-4 py-3.5 overflow-hidden">
                            <template x-if="r.customer">
                                <div>
                                    <a :href="r.customer.url"
                                       class="font-medium text-blue-600 hover:underline text-sm truncate block"
                                       x-text="r.customer.name_kana ? r.customer.name + '（' + r.customer.name_kana + '）' : r.customer.name"
                                       @click.stop></a>
                                    <div class="text-xs text-gray-400 truncate" x-text="r.customer.customer_id"></div>
                                </div>
                            </template>
                            <template x-if="!r.customer">
                                <span class="text-gray-400">—</span>
                            </template>
                        </td>

                        <td class="px-4 py-3.5 overflow-hidden">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-medium border whitespace-nowrap"
                                  :class="{
                                      'bg-blue-50 text-blue-700 border-blue-200':    r.status === 'reserved',
                                      'bg-green-50 text-green-700 border-green-200': r.status === 'completed',
                                      'bg-gray-100 text-gray-500 border-gray-200':   r.status === 'cancelled',
                                  }"
                                  x-text="{ reserved: '予約中', completed: '完了', cancelled: 'キャンセル' }[r.status] || r.status">
                            </span>
                        </td>

                        <td class="px-4 py-3.5 overflow-hidden">
                            <template x-if="r.treatment_type">
                                <div>
                                    <div class="text-gray-800 text-sm truncate" x-text="r.treatment_type"></div>
                                    <div class="text-xs text-gray-400 truncate" x-show="r.treatment_area" x-text="r.treatment_area"></div>
                                </div>
                            </template>
                            <template x-if="!r.treatment_type">
                                <span class="text-gray-400">—</span>
                            </template>
                        </td>

                        <td class="px-4 py-3.5 text-sm text-gray-600" x-text="r.staff || '—'"></td>

                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <div class="mt-4" x-html="pagination" @click="handlePagination($event)"></div>
</div>

</div>

<script>
function reservationSearch() {
    return {
        filters: {
            reservation_id:  '',
            search:          '',
            date_from:       '',
            date_to:         '',
            statuses:        [],
            treatment_types: [],
        },
        sort:        { column: '', dir: 'desc' },
        currentPage: 1,
        reservations: [],
        total:       0,
        pagination:  '',
        loading:     false,
        showFilters: false,

        get filterCount() {
            return this.filters.statuses.length
                + this.filters.treatment_types.length;
        },

        get activeFilterCount() {
            return (this.filters.reservation_id ? 1 : 0)
                + (this.filters.search ? 1 : 0)
                + (this.filters.date_from ? 1 : 0)
                + (this.filters.date_to ? 1 : 0)
                + this.filters.statuses.length
                + this.filters.treatment_types.length;
        },

        init() { this.search(); },

        setSort(column, dir) {
            if (this.sort.column === column && this.sort.dir === dir) {
                this.sort.column = ''; this.sort.dir = 'desc';
            } else {
                this.sort.column = column; this.sort.dir = dir;
            }
            this.search();
        },

        async search(resetPage = true) {
            if (resetPage) this.currentPage = 1;
            this.loading = true;
            const params = new URLSearchParams();
            if (this.filters.reservation_id) params.set('reservation_id', this.filters.reservation_id);
            if (this.filters.search)         params.set('search', this.filters.search);
            if (this.filters.date_from)      params.set('date_from', this.filters.date_from);
            if (this.filters.date_to)        params.set('date_to', this.filters.date_to);
            this.filters.statuses.forEach(v => params.append('statuses[]', v));
            this.filters.treatment_types.forEach(v => params.append('treatment_types[]', v));
            if (this.sort.column) { params.set('sort', this.sort.column); params.set('dir', this.sort.dir); }
            if (this.currentPage > 1) params.set('page', this.currentPage);

            const res = await fetch(`{{ route('reservations.index') }}?${params}`, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            this.reservations = data.reservations;
            this.total        = data.total;
            this.pagination   = data.pagination;
            this.loading      = false;
        },

        handlePagination(event) {
            const link = event.target.closest('a[href]');
            if (!link) return;
            event.preventDefault();
            const url = new URL(link.href);
            this.currentPage = parseInt(url.searchParams.get('page') || 1);
            this.search(false);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        clearFilters() {
            this.filters = { reservation_id: '', search: '', date_from: '', date_to: '', statuses: [], treatment_types: [] };
            this.sort = { column: '', dir: 'desc' };
            this.search();
        },
    };
}
</script>

@endsection
