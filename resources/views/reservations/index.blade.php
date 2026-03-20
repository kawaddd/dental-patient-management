@extends('layouts.app')

@section('title', '予約一覧')

@section('content')

<div x-data="reservationSearch()" x-init="init()" @keydown.window.escape="clearFilters()">

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-900">予約一覧</h1>
    <button @click="showFilters = !showFilters"
            class="flex items-center gap-2 px-3 py-2 bg-white border border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors min-h-[44px] lg:hidden">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
        </svg>
        フィルター
        <span x-show="activeFilterCount > 0"
              class="bg-blue-600 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center font-bold leading-none"
              x-text="activeFilterCount"></span>
    </button>
</div>

<div class="flex gap-5">

    {{-- 左: フィルタ --}}
    <div class="w-60 shrink-0 space-y-3"
         x-show="showFilters || isLg"
         x-cloak>

        {{-- 患者名/ID --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">患者名 / 患者ID</p>
            <input type="text" x-model="filters.search" @input.debounce.400ms="search()"
                   class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                   placeholder="例: 山田、P001">
        </div>

        {{-- ステータス --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">
                ステータス
                <span x-show="filters.statuses.length > 0"
                      class="ml-1 bg-blue-100 text-blue-700 text-xs px-1.5 py-0.5 rounded-full"
                      x-text="filters.statuses.length"></span>
            </p>
            <div class="space-y-1.5">
                @foreach(['reserved' => '予約中', 'completed' => '完了', 'cancelled' => 'キャンセル'] as $val => $label)
                <label class="flex items-center gap-2.5 cursor-pointer min-h-[36px] group">
                    <input type="checkbox" value="{{ $val }}"
                           x-model="filters.statuses" @change="search()"
                           class="w-4 h-4 rounded text-blue-600 border-gray-300">
                    <span class="text-sm text-gray-700 group-hover:text-gray-900">{{ $label }}</span>
                </label>
                @endforeach
            </div>
        </div>

        {{-- 予約日 --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">予約日</p>
            <input type="date" x-model="filters.date" @change="search()"
                   class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        {{-- 治療内容 --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">
                治療内容
                <span x-show="filters.treatment_types.length > 0"
                      class="ml-1 bg-blue-100 text-blue-700 text-xs px-1.5 py-0.5 rounded-full"
                      x-text="filters.treatment_types.length"></span>
            </p>
            <div class="max-h-56 overflow-y-auto space-y-0.5 -mx-1 px-1">
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
                            class="flex items-center justify-between w-full text-xs font-semibold text-gray-400 py-2 hover:text-gray-600 transition-colors">
                        <span>{{ $group }}</span>
                        <svg class="w-3 h-3 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open" x-collapse class="space-y-0.5 pb-1">
                        @foreach($items as $item)
                        <label class="flex items-center gap-2.5 cursor-pointer min-h-[32px] group pl-1">
                            <input type="checkbox" value="{{ $item }}"
                                   x-model="filters.treatment_types" @change="search()"
                                   class="w-4 h-4 rounded text-blue-600 border-gray-300 shrink-0">
                            <span class="text-xs text-gray-600 group-hover:text-gray-900 leading-snug">{{ $item }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <button @click="clearFilters()"
                class="w-full text-center text-xs text-gray-400 hover:text-gray-600 py-2 transition-colors">
            すべてクリア
        </button>

    </div>

    {{-- 右: テーブル --}}
    <div class="flex-1 min-w-0">

        <div class="flex items-center justify-between mb-3">
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

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50">

                        {{-- 予約日時 --}}
                        <th class="px-4 py-3 text-left w-32">
                            <div class="flex items-center gap-1.5">
                                <span class="text-xs font-semibold uppercase tracking-wide"
                                      :class="sort.column === 'reserved_at' ? 'text-blue-600' : 'text-gray-400'">予約日時</span>
                                <div class="flex flex-col gap-px">
                                    <button @click="setSort('reserved_at', 'asc')"
                                            :class="sort.column === 'reserved_at' && sort.dir === 'asc' ? 'text-blue-600' : 'text-gray-300 hover:text-gray-500'">
                                        <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 10 6"><path d="M5 0l5 6H0z"/></svg>
                                    </button>
                                    <button @click="setSort('reserved_at', 'desc')"
                                            :class="sort.column === 'reserved_at' && sort.dir === 'desc' ? 'text-blue-600' : 'text-gray-300 hover:text-gray-500'">
                                        <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 10 6"><path d="M5 6L0 0h10z"/></svg>
                                    </button>
                                </div>
                            </div>
                        </th>

                        {{-- 患者 --}}
                        <th class="px-4 py-3 text-left">
                            <div class="flex items-center gap-1.5">
                                <span class="text-xs font-semibold uppercase tracking-wide"
                                      :class="sort.column === 'customer' ? 'text-blue-600' : 'text-gray-400'">患者</span>
                                <div class="flex flex-col gap-px">
                                    <button @click="setSort('customer', 'asc')"
                                            :class="sort.column === 'customer' && sort.dir === 'asc' ? 'text-blue-600' : 'text-gray-300 hover:text-gray-500'">
                                        <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 10 6"><path d="M5 0l5 6H0z"/></svg>
                                    </button>
                                    <button @click="setSort('customer', 'desc')"
                                            :class="sort.column === 'customer' && sort.dir === 'desc' ? 'text-blue-600' : 'text-gray-300 hover:text-gray-500'">
                                        <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 10 6"><path d="M5 6L0 0h10z"/></svg>
                                    </button>
                                </div>
                            </div>
                        </th>

                        {{-- ステータス --}}
                        <th class="px-4 py-3 text-left w-28">
                            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">ステータス</span>
                        </th>

                        {{-- 治療内容 --}}
                        <th class="px-4 py-3 text-left">
                            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">治療内容</span>
                        </th>

                        {{-- 担当者 --}}
                        <th class="px-4 py-3 text-left w-24">
                            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">担当者</span>
                        </th>

                        {{-- 予約ID --}}
                        <th class="px-4 py-3 text-left w-28">
                            <div class="flex items-center gap-1.5">
                                <span class="text-xs font-semibold uppercase tracking-wide"
                                      :class="sort.column === 'reservation_id' ? 'text-blue-600' : 'text-gray-400'">予約ID</span>
                                <div class="flex flex-col gap-px">
                                    <button @click="setSort('reservation_id', 'asc')"
                                            :class="sort.column === 'reservation_id' && sort.dir === 'asc' ? 'text-blue-600' : 'text-gray-300 hover:text-gray-500'">
                                        <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 10 6"><path d="M5 0l5 6H0z"/></svg>
                                    </button>
                                    <button @click="setSort('reservation_id', 'desc')"
                                            :class="sort.column === 'reservation_id' && sort.dir === 'desc' ? 'text-blue-600' : 'text-gray-300 hover:text-gray-500'">
                                        <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 10 6"><path d="M5 6L0 0h10z"/></svg>
                                    </button>
                                </div>
                            </div>
                        </th>

                    </tr>
                </thead>
                <tbody>
                    <template x-if="loading">
                        <tr>
                            <td colspan="6" class="px-6 py-14 text-center">
                                <svg class="w-6 h-6 animate-spin text-blue-400 mx-auto" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                            </td>
                        </tr>
                    </template>

                    <template x-if="!loading && reservations.length === 0">
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center text-gray-400 text-sm">
                                該当する予約データがありません
                            </td>
                        </tr>
                    </template>

                    <template x-for="r in reservations" :key="r.id">
                        <tr class="border-b border-gray-50 last:border-0 hover:bg-blue-50 transition-colors">

                            {{-- 予約日時 --}}
                            <td class="px-4 py-3.5">
                                <div class="font-medium text-gray-800" x-text="r.reserved_date"></div>
                                <div class="text-xs text-gray-400" x-text="r.reserved_time"></div>
                            </td>

                            {{-- 患者 --}}
                            <td class="px-4 py-3.5">
                                <template x-if="r.customer">
                                    <div>
                                        <a :href="r.customer.url"
                                           class="font-medium text-blue-600 hover:underline"
                                           x-text="r.customer.name_kana ? r.customer.name + '（' + r.customer.name_kana + '）' : r.customer.name"
                                           @click.stop></a>
                                        <div class="text-xs text-gray-400" x-text="r.customer.customer_id"></div>
                                    </div>
                                </template>
                                <template x-if="!r.customer">
                                    <span class="text-gray-400">—</span>
                                </template>
                            </td>

                            {{-- ステータス --}}
                            <td class="px-4 py-3.5">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-medium border"
                                      :class="{
                                          'bg-blue-50 text-blue-700 border-blue-200':   r.status === 'reserved',
                                          'bg-green-50 text-green-700 border-green-200': r.status === 'completed',
                                          'bg-gray-100 text-gray-500 border-gray-200':  r.status === 'cancelled',
                                      }"
                                      x-text="{ reserved: '予約中', completed: '完了', cancelled: 'キャンセル' }[r.status] || r.status">
                                </span>
                            </td>

                            {{-- 治療内容 --}}
                            <td class="px-4 py-3.5">
                                <template x-if="r.treatment_type">
                                    <div>
                                        <div class="text-gray-800" x-text="r.treatment_type"></div>
                                        <div class="text-xs text-gray-400" x-show="r.treatment_area" x-text="r.treatment_area"></div>
                                    </div>
                                </template>
                                <template x-if="!r.treatment_type">
                                    <span class="text-gray-400">—</span>
                                </template>
                            </td>

                            {{-- 担当者 --}}
                            <td class="px-4 py-3.5 text-gray-600" x-text="r.staff || '—'"></td>

                            {{-- 予約ID --}}
                            <td class="px-4 py-3.5 text-xs text-gray-400 font-mono" x-text="r.reservation_id"></td>

                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div class="mt-4" x-html="pagination" @click="handlePagination($event)"></div>
    </div>

</div>

</div>

<script>
function reservationSearch() {
    return {
        filters: {
            search:          '',
            statuses:        [],
            date:            '',
            treatment_types: [],
        },
        sort:        { column: '', dir: 'asc' },
        currentPage: 1,
        reservations: [],
        total:       0,
        pagination:  '',
        loading:     false,
        showFilters: false,
        isLg:        window.innerWidth >= 1024,

        get activeFilterCount() {
            return (this.filters.search ? 1 : 0)
                + this.filters.statuses.length
                + (this.filters.date ? 1 : 0)
                + this.filters.treatment_types.length;
        },

        init() {
            window.addEventListener('resize', () => {
                this.isLg = window.innerWidth >= 1024;
            });
            this.search();
        },

        setSort(column, dir) {
            if (this.sort.column === column && this.sort.dir === dir) {
                this.sort.column = '';
                this.sort.dir    = 'asc';
            } else {
                this.sort.column = column;
                this.sort.dir    = dir;
            }
            this.search();
        },

        async search(resetPage = true) {
            if (resetPage) this.currentPage = 1;
            this.loading = true;

            const params = new URLSearchParams();
            if (this.filters.search) params.set('search', this.filters.search);
            this.filters.statuses.forEach(v => params.append('statuses[]', v));
            if (this.filters.date) params.set('date', this.filters.date);
            this.filters.treatment_types.forEach(v => params.append('treatment_types[]', v));
            if (this.sort.column) {
                params.set('sort', this.sort.column);
                params.set('dir',  this.sort.dir);
            }
            if (this.currentPage > 1) params.set('page', this.currentPage);

            const res = await fetch(`{{ route('reservations.index') }}?${params}`, {
                headers: { 'Accept': 'application/json' }
            });
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
            this.filters = { search: '', statuses: [], date: '', treatment_types: [] };
            this.sort    = { column: 'reserved_at', dir: 'desc' };
            this.search();
        },
    };
}
</script>

@endsection
