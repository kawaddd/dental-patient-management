@extends('layouts.app')

@section('title', '患者一覧')

@section('content')

<div x-data="customerSearch()" x-init="init()" @keydown.window.escape="clearFilters()">

{{-- ページヘッダー --}}
<div class="flex items-center justify-between mb-3">
    <h1 class="text-2xl font-bold text-gray-900">患者一覧</h1>
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
            <label class="block text-xs text-gray-400 mb-1">患者ID</label>
            <input type="text" x-model="filters.customer_id" @input.debounce.400ms="search()"
                   class="w-full border border-gray-200 rounded-lg px-2.5 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-50 focus:bg-white transition-all"
                   placeholder="P001">
        </div>

        <div>
            <label class="block text-xs text-gray-400 mb-1">患者 / 患者（カナ）</label>
            <input type="text" x-model="filters.name" @input.debounce.400ms="search()"
                   class="w-full border border-gray-200 rounded-lg px-2.5 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-50 focus:bg-white transition-all"
                   placeholder="山田 / ヤマダ">
        </div>

        <div>
            <label class="block text-xs text-gray-400 mb-1">最終診療日（開始）</label>
            <input type="date" x-model="filters.last_visit_from"
                   :max="filters.last_visit_to || ''"
                   @change="if (filters.last_visit_to && $event.target.value > filters.last_visit_to) { filters.last_visit_to = '' } search()"
                   class="w-full border border-gray-200 rounded-lg px-2.5 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-50 focus:bg-white transition-all">
        </div>

        <div>
            <label class="block text-xs text-gray-400 mb-1">最終診療日（終了）</label>
            <input type="date" x-model="filters.last_visit_to"
                   :min="filters.last_visit_from || ''"
                   @change="if (filters.last_visit_from && $event.target.value < filters.last_visit_from) { filters.last_visit_from = '' } search()"
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

        {{-- 院 --}}
        <div>
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5">
                院
                <span x-show="filters.store_ids.length > 0"
                      class="ml-1 bg-blue-100 text-blue-700 text-xs px-1.5 py-0.5 rounded-full"
                      x-text="filters.store_ids.length"></span>
            </p>
            <div class="space-y-1 max-h-44 overflow-y-auto">
                @foreach($stores as $store)
                <label class="flex items-center gap-2 cursor-pointer group">
                    <input type="checkbox" value="{{ $store->id }}"
                           x-model="filters.store_ids" @change="search()"
                           class="w-3.5 h-3.5 rounded text-blue-600 border-gray-300 shrink-0">
                    <span class="text-sm text-gray-700 group-hover:text-gray-900">{{ $store->name }}</span>
                </label>
                @endforeach
            </div>
        </div>

        {{-- 治療内容 --}}
        <div>
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
                        <div class="grid grid-cols-2 gap-x-2 gap-y-0 pb-1">
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

        {{-- 治療部位 --}}
        <div>
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5">
                治療部位
                <span x-show="filters.treatment_areas.length > 0"
                      class="ml-1 bg-blue-100 text-blue-700 text-xs px-1.5 py-0.5 rounded-full"
                      x-text="filters.treatment_areas.length"></span>
            </p>
            <div class="max-h-44 overflow-y-auto space-y-0 pr-1">
                @foreach([
                    '右上' => ['右上1番','右上2番','右上3番','右上4番','右上5番','右上6番','右上7番','右上8番'],
                    '左上' => ['左上1番','左上2番','左上3番','左上4番','左上5番','左上6番','左上7番','左上8番'],
                    '右下' => ['右下1番','右下2番','右下3番','右下4番','右下5番','右下6番','右下7番','右下8番'],
                    '左下' => ['左下1番','左下2番','左下3番','左下4番','左下5番','左下6番','左下7番','左下8番'],
                    'エリア' => ['上顎前歯部','下顎前歯部','上顎臼歯部','下顎臼歯部','上顎全体','下顎全体','全顎'],
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
                        <div class="grid grid-cols-2 gap-x-2 gap-y-0 pb-1">
                            @foreach($items as $item)
                            <label class="flex items-center gap-1.5 cursor-pointer group py-0.5">
                                <input type="checkbox" value="{{ $item }}"
                                       x-model="filters.treatment_areas" @change="search()"
                                       class="w-3.5 h-3.5 rounded text-blue-600 border-gray-300 shrink-0">
                                <span class="text-xs text-gray-600 group-hover:text-gray-900 leading-tight">{{ $item }}</span>
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
        <table class="w-full" style="table-layout: fixed; min-width: 560px;">
            <colgroup>
                <col :style="`width: ${colWidths.customer_id}px; min-width: 80px`">
                <col :style="`width: ${colWidths.name}px; min-width: 120px`">
                <col :style="`width: ${colWidths.store}px; min-width: 80px`">
                <col :style="`width: ${colWidths.last_visit}px; min-width: 90px`">
                <col style="width: 32px">
            </colgroup>
            <thead>
                <tr class="border-b border-gray-100 bg-gray-50">
                    <th class="px-4 py-3 relative select-none">
                        <div class="flex items-center gap-1.5">
                            <span class="text-xs font-semibold uppercase tracking-wide"
                                  :class="sort.column === 'customer_id' ? 'text-blue-600' : 'text-gray-400'">患者ID</span>
                            <div class="flex flex-col gap-px">
                                <button @click="setSort('customer_id', 'asc')" :class="sort.column === 'customer_id' && sort.dir === 'asc' ? 'text-blue-600' : 'text-gray-300 hover:text-gray-500'">
                                    <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 10 6"><path d="M5 0l5 6H0z"/></svg>
                                </button>
                                <button @click="setSort('customer_id', 'desc')" :class="sort.column === 'customer_id' && sort.dir === 'desc' ? 'text-blue-600' : 'text-gray-300 hover:text-gray-500'">
                                    <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 10 6"><path d="M5 6L0 0h10z"/></svg>
                                </button>
                            </div>
                        </div>
                        <div class="absolute right-0 top-0 h-full w-1 cursor-col-resize hover:bg-blue-300 transition-colors" @mousedown.prevent="startResize($event, 'customer_id')"></div>
                    </th>
                    <th class="px-4 py-3 relative select-none">
                        <div class="flex items-center gap-1.5">
                            <span class="text-xs font-semibold uppercase tracking-wide"
                                  :class="sort.column === 'name' ? 'text-blue-600' : 'text-gray-400'">患者名</span>
                            <div class="flex flex-col gap-px">
                                <button @click="setSort('name', 'asc')" :class="sort.column === 'name' && sort.dir === 'asc' ? 'text-blue-600' : 'text-gray-300 hover:text-gray-500'">
                                    <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 10 6"><path d="M5 0l5 6H0z"/></svg>
                                </button>
                                <button @click="setSort('name', 'desc')" :class="sort.column === 'name' && sort.dir === 'desc' ? 'text-blue-600' : 'text-gray-300 hover:text-gray-500'">
                                    <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 10 6"><path d="M5 6L0 0h10z"/></svg>
                                </button>
                            </div>
                        </div>
                        <div class="absolute right-0 top-0 h-full w-1 cursor-col-resize hover:bg-blue-300 transition-colors" @mousedown.prevent="startResize($event, 'name')"></div>
                    </th>
                    <th class="px-4 py-3 relative select-none text-left text-xs font-semibold text-gray-400 uppercase tracking-wide">
                        院
                        <div class="absolute right-0 top-0 h-full w-1 cursor-col-resize hover:bg-blue-300 transition-colors" @mousedown.prevent="startResize($event, 'store')"></div>
                    </th>
                    <th class="px-4 py-3 relative select-none">
                        <div class="flex items-center gap-1.5">
                            <span class="text-xs font-semibold uppercase tracking-wide"
                                  :class="sort.column === 'last_visit' ? 'text-blue-600' : 'text-gray-400'">最終診療日</span>
                            <div class="flex flex-col gap-px">
                                <button @click="setSort('last_visit', 'asc')" :class="sort.column === 'last_visit' && sort.dir === 'asc' ? 'text-blue-600' : 'text-gray-300 hover:text-gray-500'">
                                    <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 10 6"><path d="M5 0l5 6H0z"/></svg>
                                </button>
                                <button @click="setSort('last_visit', 'desc')" :class="sort.column === 'last_visit' && sort.dir === 'desc' ? 'text-blue-600' : 'text-gray-300 hover:text-gray-500'">
                                    <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 10 6"><path d="M5 6L0 0h10z"/></svg>
                                </button>
                            </div>
                        </div>
                    </th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <template x-if="loading">
                    <tr><td colspan="5" class="px-6 py-14 text-center">
                        <svg class="w-6 h-6 animate-spin text-blue-400 mx-auto" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </td></tr>
                </template>
                <template x-if="!loading && customers.length === 0">
                    <tr><td colspan="5" class="px-6 py-16 text-center">
                        <svg class="w-10 h-10 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <p class="text-sm text-gray-400">患者が見つかりませんでした</p>
                    </td></tr>
                </template>
                <template x-for="customer in customers" :key="customer.id">
                    <tr @click="window.location.href = customer.url"
                        class="border-b border-gray-50 last:border-0 hover:bg-blue-50 cursor-pointer transition-colors">
                        <td class="px-4 py-3.5 overflow-hidden">
                            <span class="text-sm font-mono text-gray-400 truncate block" x-text="customer.customer_id"></span>
                        </td>
                        <td class="px-4 py-3.5 overflow-hidden">
                            <span class="font-semibold text-gray-900 truncate block" x-text="customer.name_kana ? customer.name + '（' + customer.name_kana + '）' : customer.name"></span>
                        </td>
                        <td class="px-4 py-3.5 overflow-hidden">
                            <span class="text-sm text-gray-600 truncate block" x-text="customer.store_name"></span>
                        </td>
                        <td class="px-4 py-3.5 overflow-hidden">
                            <span class="text-sm text-gray-500" x-text="customer.last_treatment_date"></span>
                        </td>
                        <td class="px-4 py-3.5 text-right">
                            <svg class="w-4 h-4 text-gray-300 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <div class="mt-4" x-html="pagination" @click="handlePagination($event)"></div>
</div>

</div>

<script>
function customerSearch() {
    return {
        filters: {
            customer_id:     '',
            name:            '',
            last_visit_from: '',
            last_visit_to:   '',
            store_ids:       [],
            treatment_types: [],
            treatment_areas: [],
        },
        sort:        { column: '', dir: 'asc' },
        colWidths:   { customer_id: 90, name: 200, store: 150, last_visit: 110 },
        currentPage: 1,
        customers:   [],
        total:       0,
        pagination:  '',
        loading:     false,
        showFilters: false,

        get filterCount() {
            return this.filters.store_ids.length
                + this.filters.treatment_types.length
                + this.filters.treatment_areas.length;
        },

        get activeFilterCount() {
            return (this.filters.customer_id ? 1 : 0)
                + (this.filters.name ? 1 : 0)
                + (this.filters.last_visit_from ? 1 : 0)
                + (this.filters.last_visit_to ? 1 : 0)
                + this.filters.store_ids.length
                + this.filters.treatment_types.length
                + this.filters.treatment_areas.length;
        },

        init() { this.search(); },

        startResize(event, col) {
            const startX = event.pageX;
            const startW = this.colWidths[col];
            const onMove = (e) => { this.colWidths[col] = Math.max(60, startW + e.pageX - startX); };
            const onUp = () => {
                document.removeEventListener('mousemove', onMove);
                document.removeEventListener('mouseup', onUp);
            };
            document.addEventListener('mousemove', onMove);
            document.addEventListener('mouseup', onUp);
        },

        setSort(column, dir) {
            if (this.sort.column === column && this.sort.dir === dir) {
                this.sort.column = ''; this.sort.dir = 'asc';
            } else {
                this.sort.column = column; this.sort.dir = dir;
            }
            this.search();
        },

        async search(resetPage = true) {
            if (resetPage) this.currentPage = 1;
            this.loading = true;
            const params = new URLSearchParams();
            if (this.filters.customer_id)     params.set('customer_id', this.filters.customer_id);
            if (this.filters.name)            params.set('name', this.filters.name);
            if (this.filters.last_visit_from) params.set('last_visit_from', this.filters.last_visit_from);
            if (this.filters.last_visit_to)   params.set('last_visit_to', this.filters.last_visit_to);
            this.filters.store_ids.forEach(v => params.append('store_ids[]', v));
            this.filters.treatment_types.forEach(v => params.append('treatment_types[]', v));
            this.filters.treatment_areas.forEach(v => params.append('treatment_areas[]', v));
            if (this.sort.column) { params.set('sort', this.sort.column); params.set('dir', this.sort.dir); }
            if (this.currentPage > 1) params.set('page', this.currentPage);

            const res = await fetch(`{{ route('customers.index') }}?${params}`, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            this.customers  = data.customers;
            this.total      = data.total;
            this.pagination = data.pagination;
            this.loading    = false;
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
            this.filters = { customer_id: '', name: '', last_visit_from: '', last_visit_to: '', store_ids: [], treatment_types: [], treatment_areas: [] };
            this.sort = { column: '', dir: 'asc' };
            this.search();
        },
    };
}
</script>
@endsection
