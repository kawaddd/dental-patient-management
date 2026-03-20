# TICKET-04: 患者詳細画面（診療履歴タイムライン）

← [一覧に戻る](./README.md)

**ステータス**: ⬜ 未着手

**依存**: TICKET-00（共通基盤）完了済みであること

**概要**: 患者の基本情報と診療履歴をタイムライン形式で表示する。治療部位フィルタで絞り込みができる。このアプリの最重要画面。

---

## 作業内容

### 1. 専用 Seeder（このチケット単体で確認するためのデータ）

```bash
php artisan make:seeder CustomerDetailSeeder
```

```php
// database/seeders/CustomerDetailSeeder.php
public function run(): void
{
    // 院（歯科クリニック）
    $store = Store::firstOrCreate(
        ['store_code' => 'C001'],
        ['name' => '渋谷歯科クリニック']
    );

    // 患者1件（詳細確認用）
    $customer = Customer::firstOrCreate(
        ['customer_id' => 'P001'],
        [
            'name'       => '山田 太郎',
            'birth_date' => '1990-05-15',
            'phone'      => '090-1234-5678',
            'store_id'   => $store->id,
        ]
    );

    // 診療履歴（12件）
    // treatment_type（治療内容）と treatment_area（治療部位）を分けて持つ
    // ※ 部位を特定しない治療（ホワイトニング・矯正等）は treatment_area = null
    $histories = [
        // 直近: 根管治療の一連の流れ（同じ歯を複数回）
        ['treatment_type' => 'レジン充填（白い詰め物）',   'treatment_area' => '右上7番',   'treated_at' => now()->subDays(5),   'staff' => '佐藤先生'],
        ['treatment_type' => '根管治療（神経治療）',        'treatment_area' => '右上7番',   'treated_at' => now()->subDays(20),  'staff' => '佐藤先生'],
        ['treatment_type' => '抜髄（神経を抜く）',         'treatment_area' => '右上7番',   'treated_at' => now()->subDays(35),  'staff' => '佐藤先生'],
        // 予防・クリーニング
        ['treatment_type' => 'クリーニング（PMTC）',       'treatment_area' => '全顎',      'treated_at' => now()->subDays(60),  'staff' => '田中先生'],
        ['treatment_type' => '歯石除去（スケーリング）',    'treatment_area' => '上顎全体',  'treated_at' => now()->subDays(60),  'staff' => '田中先生'],
        // 虫歯治療
        ['treatment_type' => '虫歯治療',                 'treatment_area' => '左下6番',   'treated_at' => now()->subDays(95),  'staff' => '鈴木先生'],
        ['treatment_type' => 'セラミックインレー',         'treatment_area' => '左下6番',   'treated_at' => now()->subDays(110), 'staff' => '鈴木先生'],
        // 補綴
        ['treatment_type' => 'ジルコニアクラウン',         'treatment_area' => '右上6番',   'treated_at' => now()->subDays(150), 'staff' => '佐藤先生'],
        // 審美
        ['treatment_type' => 'オフィスホワイトニング',     'treatment_area' => null,        'treated_at' => now()->subDays(180), 'staff' => '田中先生'],
        // 親知らず
        ['treatment_type' => '親知らず抜歯',             'treatment_area' => '左下8番',   'treated_at' => now()->subDays(240), 'staff' => '田中先生'],
        // 歯周病
        ['treatment_type' => '歯周病治療',               'treatment_area' => '下顎全体',  'treated_at' => now()->subDays(300), 'staff' => '鈴木先生'],
        // 初診
        ['treatment_type' => '初診検査',                 'treatment_area' => null,        'treated_at' => now()->subDays(365), 'staff' => '佐藤先生'],
    ];

    foreach ($histories as $data) {
        // 予約レコード
        $reservation = Reservation::create([
            'reservation_id' => 'R' . uniqid(),
            'customer_id'    => $customer->id,
            'reserved_at'    => $data['treated_at'],
            'staff'          => $data['staff'],
            'status'         => 'completed',
        ]);

        // 診療履歴
        TreatmentHistory::firstOrCreate(
            [
                'customer_id'    => $customer->id,
                'treated_at'     => $data['treated_at'],
                'treatment_type' => $data['treatment_type'],
            ],
            [
                'reservation_id' => $reservation->id,
                'treatment_area' => $data['treatment_area'],
                'staff'          => $data['staff'],
            ]
        );
    }

    echo "顧客ID: {$customer->id} でアクセスしてください\n";
    echo "URL: /customers/{$customer->id}\n";
}
```

```bash
php artisan db:seed --class=CustomerDetailSeeder
```

---

### 2. CustomerController#show

**`app/Http/Controllers/CustomerController.php`** に `show` メソッドを追加:

```php
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

    // フィルタ用の選択肢（治療内容・治療部位 それぞれ distinct で取得）
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
```

---

### 3. Bladeビュー

**`resources/views/customers/show.blade.php`**

```html
@extends('layouts.app')

@section('title', $customer->name . ' - 顧客詳細')

@section('content')

{{-- 戻るリンク --}}
<a href="{{ route('customers.index') }}"
   class="inline-flex items-center text-blue-600 hover:underline mb-4 text-sm">
    ← 顧客一覧に戻る
</a>

{{-- 顧客基本情報カード --}}
<x-card class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">{{ $customer->name }}</h2>
            <p class="text-gray-500 mt-1">顧客ID: {{ $customer->customer_id }}</p>
        </div>
        <div class="text-right text-sm text-gray-500">
            <p>{{ $customer->store->name ?? '-' }}</p>
            @if($customer->birth_date)
                <p>{{ \Carbon\Carbon::parse($customer->birth_date)->format('Y年m月d日') }}生</p>
            @endif
            @if($customer->phone)
                <p>{{ $customer->phone }}</p>
            @endif
        </div>
    </div>
</x-card>

{{-- フィルタ（治療内容・治療部位 独立） --}}
<div class="flex gap-6 mb-6">

    {{-- 治療内容フィルタ --}}
    <div>
        <p class="text-xs text-gray-400 mb-2">治療内容</p>
        <div class="flex gap-2 flex-wrap">
            <a href="{{ route('customers.show', array_merge(request()->except('type'), ['customer' => $customer->id])) }}"
               class="px-3 py-1.5 rounded-full text-sm border
                      {{ !request('type') ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50' }}">
                すべて
            </a>
            @foreach($types as $type)
                <a href="{{ route('customers.show', array_merge(request()->all(), ['customer' => $customer->id, 'type' => $type])) }}"
                   class="px-3 py-1.5 rounded-full text-sm border
                          {{ request('type') === $type ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50' }}">
                    {{ $type }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- 治療部位フィルタ --}}
    @if($areas->isNotEmpty())
    <div>
        <p class="text-xs text-gray-400 mb-2">治療部位</p>
        <div class="flex gap-2 flex-wrap">
            <a href="{{ route('customers.show', array_merge(request()->except('area'), ['customer' => $customer->id])) }}"
               class="px-3 py-1.5 rounded-full text-sm border
                      {{ !request('area') ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50' }}">
                すべて
            </a>
            @foreach($areas as $area)
                <a href="{{ route('customers.show', array_merge(request()->all(), ['customer' => $customer->id, 'area' => $area])) }}"
                   class="px-3 py-1.5 rounded-full text-sm border
                          {{ request('area') === $area ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50' }}">
                    {{ $area }}
                </a>
            @endforeach
        </div>
    </div>
    @endif

</div>

{{-- 診療履歴タイムライン --}}
<div class="relative">
    @forelse($histories as $history)
        <div class="flex gap-4 mb-6">
            {{-- タイムライン縦線と丸 --}}
            <div class="flex flex-col items-center">
                <div class="w-3 h-3 rounded-full bg-blue-500 mt-1 shrink-0"></div>
                @unless($loop->last)
                    <div class="w-0.5 flex-1 bg-gray-200 mt-1"></div>
                @endunless
            </div>

            {{-- 履歴カード --}}
            <x-card class="flex-1 mb-0">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="font-semibold text-lg text-gray-800">{{ $history->treatment_type }}</p>
                        @if($history->treatment_area)
                            <p class="text-sm text-blue-600 mt-0.5">{{ $history->treatment_area }}</p>
                        @endif
                        <p class="text-gray-500 text-sm mt-1">
                            {{ $history->treated_at->format('Y年m月d日 H:i') }}
                        </p>
                        @if($history->staff)
                            <p class="text-sm text-gray-600 mt-1">担当: {{ $history->staff }}</p>
                        @endif
                        @if($history->notes)
                            <p class="text-sm text-gray-500 mt-2 border-t pt-2">{{ $history->notes }}</p>
                        @endif
                    </div>
                    <x-badge :status="$history->reservation?->status ?? 'completed'" />
                </div>
            </x-card>
        </div>
    @empty
        <x-card>
            <p class="text-gray-400 text-center py-8">施術履歴はありません</p>
        </x-card>
    @endforelse
</div>

@endsection
```

---

## 確認手順（単体）

```bash
# 1. Seederでデータ投入（1顧客 + 10件の施術履歴）
php artisan db:seed --class=CustomerDetailSeeder
# → "顧客ID: X でアクセスしてください" が出力される

# 2. サーバー起動
php artisan serve
npm run dev
```

ブラウザで確認:
1. `http://localhost:8000/customers/1`（Seeder出力のIDを使用）にアクセス
2. 顧客基本情報カード（名前・顧客ID・店舗・生年月日・電話番号）が表示される
3. 施術部位フィルタボタンが表示される（すべて・脱毛-脇・フェイシャル・脱毛-VIO・脱毛-ひざ下）
4. 診療履歴が新しい順でタイムライン表示される（10件）
5. 治療内容「虫歯治療」フィルタをタップ → 虫歯治療の履歴のみ表示される（3件）
6. 治療部位「右上7番」フィルタをタップ → 右上7番の履歴のみ表示される
7. 「虫歯治療」+「右上7番」の両方を選択 → AND絞り込みされる
8. ホワイトニングの履歴は治療部位が空欄で正常に表示される
6. 「すべて」をタップ → 全履歴に戻る（10件）
7. 各履歴カードにステータスバッジが色付きで表示される

---

## 完了条件

- [ ] `/customers/{id}` にアクセスすると患者詳細画面が表示される
- [ ] 患者基本情報（名前・患者ID・院・生年月日・電話番号）が表示される
- [ ] 診療履歴が新しい順でタイムライン形式で表示される
- [ ] 各履歴カードに治療内容（太字）と治療部位（青文字）が表示される
- [ ] 治療部位が空欄の履歴は治療内容のみ表示される
- [ ] 治療内容フィルタボタンが表示され、タップで絞り込みができる
- [ ] 治療部位フィルタボタンが表示され、タップで絞り込みができる
- [ ] 治療内容と治療部位を同時に選択するとAND絞り込みされる
- [ ] 各フィルタの「すべて」で個別にリセットできる
- [ ] 各履歴カードに `<x-badge>` でステータスが色付きで表示される
- [ ] 診療履歴が0件の場合に「診療履歴はありません」が表示される
- [ ] 存在しない患者IDにアクセスすると404になる
- [ ] 「患者一覧に戻る」リンクが動作する
