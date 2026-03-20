# TICKET-02: ダッシュボード画面

← [一覧に戻る](./README.md)

**ステータス**: ⬜ 未着手

**依存**: TICKET-00（共通基盤）完了済みであること

**概要**: ログイン後のトップ画面。患者総数・最終インポート情報・最近の診療履歴の3枚サマリーカードを表示する。

---

## 作業内容

### 1. 専用 Seeder（このチケット単体で確認するためのデータ）

```bash
php artisan make:seeder DashboardSeeder
```

```php
// database/seeders/DashboardSeeder.php
public function run(): void
{
    // 院（歯科クリニック）
    $store = Store::firstOrCreate(
        ['store_code' => 'C001'],
        ['name' => '渋谷歯科クリニック']
    );

    // 患者（5件）
    $customers = collect(range(1, 5))->map(fn($i) => Customer::firstOrCreate(
        ['customer_id' => "P00{$i}"],
        [
            'name'     => "テスト患者{$i}",
            'store_id' => $store->id,
        ]
    ));

    // 診療履歴（各患者2件ずつ）
    $types = ['虫歯治療', '根管治療（神経治療）', '歯石除去（スケーリング）', 'クリーニング（PMTC）', '定期健診'];
    $areas = ['右上6番', '左下7番', '上顎全体', '全顎', null];

    $customers->each(function ($customer) use ($types, $areas) {
        collect(range(1, 2))->each(function () use ($customer, $types, $areas) {
            TreatmentHistory::create([
                'customer_id'    => $customer->id,
                'treatment_type' => $types[array_rand($types)],
                'treatment_area' => $areas[array_rand($areas)],
                'treated_at'     => now()->subDays(rand(1, 30)),
                'staff'          => collect(['佐藤先生', '田中先生', '鈴木先生'])->random(),
            ]);
        });
    });

    // インポート履歴（直近1件）
    ImportJob::firstOrCreate(
        ['filename' => 'patients_2026-03-20.csv'],
        [
            'type'         => 'customers',
            'status'       => 'completed',
            'total_rows'   => 100,
            'success_rows' => 98,
            'error_rows'   => 2,
        ]
    );
}
```

```bash
php artisan db:seed --class=DashboardSeeder
```

---

### 2. DashboardController

**`app/Http/Controllers/DashboardController.php`**

```php
public function index(): View
{
    return view('dashboard', [
        'customerCount'  => Customer::count(),
        'latestImport'   => ImportJob::latest()->first(),
        'recentHistories' => TreatmentHistory::with('customer')
                                ->orderByDesc('treated_at')
                                ->limit(5)
                                ->get(),
    ]);
}
```

---

### 3. Bladeビュー

**`resources/views/dashboard.blade.php`**

```html
@extends('layouts.app')

@section('title', 'ダッシュボード')

@section('content')
<h2 class="text-xl font-bold text-gray-800 mb-6">ダッシュボード</h2>

{{-- サマリーカード3枚 --}}
<div class="grid grid-cols-3 gap-4 mb-8">

    {{-- 患者総数 --}}
    <x-card>
        <p class="text-sm text-gray-500 mb-1">患者総数</p>
        <p class="text-3xl font-bold text-blue-600">{{ number_format($customerCount) }}</p>
        <p class="text-sm text-gray-400">件</p>
    </x-card>

    {{-- 最終インポート --}}
    <x-card>
        <p class="text-sm text-gray-500 mb-1">最終インポート</p>
        @if($latestImport)
            <p class="text-base font-semibold text-gray-700">{{ $latestImport->created_at->format('Y/m/d H:i') }}</p>
            <p class="text-sm text-gray-500">
                成功 {{ $latestImport->success_rows }}件
                @if($latestImport->error_rows > 0)
                    <span class="text-red-500">/ エラー {{ $latestImport->error_rows }}件</span>
                @endif
            </p>
        @else
            <p class="text-gray-400">まだインポートがありません</p>
        @endif
    </x-card>

    {{-- 最近の診療 --}}
    <x-card>
        <p class="text-sm text-gray-500 mb-1">直近の診療</p>
        <p class="text-3xl font-bold text-green-600">{{ $recentHistories->count() }}</p>
        <p class="text-sm text-gray-400">件（直近5件）</p>
    </x-card>

</div>

{{-- 最近の診療履歴リスト --}}
<x-card>
    <h3 class="font-semibold text-gray-700 mb-4">最近の診療履歴</h3>
    @forelse($recentHistories as $history)
        <div class="flex justify-between items-center py-3 border-b last:border-0">
            <div>
                <span class="font-medium">{{ $history->customer->name }}</span>
                <span class="text-gray-500 text-sm ml-2">{{ $history->treatment_area }}</span>
            </div>
            <span class="text-gray-400 text-sm">{{ $history->treated_at->format('Y/m/d') }}</span>
        </div>
    @empty
        <p class="text-gray-400">施術履歴がありません</p>
    @endforelse
</x-card>
@endsection
```

---

## 確認手順（単体）

```bash
# 1. Seederでデータ投入
php artisan db:seed --class=DashboardSeeder

# 2. サーバー起動
php artisan serve
npm run dev
```

> **認証を一時的にスキップする場合**: `routes/web.php` の auth ミドルウェアを外して直接 `/dashboard` にアクセスできる。TICKET-01完了後は不要。

ブラウザで確認:
1. `http://localhost:8000/dashboard` にアクセス
2. サマリーカード3枚が表示される
3. 顧客総数が5と表示される
4. 最終インポートの日時と件数が表示される
5. 最近の施術履歴リストに最大5件表示される

---

## 完了条件

- [ ] `/dashboard` にアクセスするとダッシュボード画面が表示される
- [ ] 「患者総数」カードに件数が表示される
- [ ] 「最終インポート」カードに日時・件数が表示される
- [ ] 「最近の診療履歴」リストに最大5件が表示される
- [ ] 診療履歴が0件の場合に「診療履歴がありません」が表示される
- [ ] サイドバーのナビゲーションが表示される（レイアウト確認）
