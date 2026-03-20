# TICKET-03: 患者一覧画面

← [一覧に戻る](./README.md)

**ステータス**: ⬜ 未着手

**依存**: TICKET-00（共通基盤）完了済みであること

**概要**: 検索フィルタ（患者ID・院・治療部位）付きの患者一覧画面。カード形式で表示し、タップで患者詳細へ遷移する。

---

## 作業内容

### 1. 専用 Seeder（このチケット単体で確認するためのデータ）

```bash
php artisan make:seeder CustomerListSeeder
```

```php
// database/seeders/CustomerListSeeder.php
public function run(): void
{
    // 院（歯科クリニック・3院）
    $stores = collect([
        ['store_code' => 'C001', 'name' => '渋谷歯科クリニック'],
        ['store_code' => 'C002', 'name' => '新宿歯科クリニック'],
        ['store_code' => 'C003', 'name' => '池袋歯科クリニック'],
    ])->map(fn($data) => Store::firstOrCreate(['store_code' => $data['store_code']], $data));

    // 患者（20件、各院に分散）
    $types = [
        '虫歯治療', '根管治療（神経治療）', '歯石除去（スケーリング）',
        'クリーニング（PMTC）', 'レジン充填（白い詰め物）', 'セラミックインレー',
        'ジルコニアクラウン', '歯周病治療', '親知らず抜歯', 'オフィスホワイトニング',
        'マウスピース矯正', 'インプラント埋入', '初診検査', '定期健診',
    ];
    $areaOptions = [
        '右上6番', '右上7番', '左上5番', '左上6番',
        '右下4番', '右下6番', '左下5番', '左下8番',
        '上顎全体', '下顎全体', '全顎', null,
    ];

    collect(range(1, 20))->each(function ($i) use ($stores, $types, $areaOptions) {
        $paddedId = str_pad($i, 3, '0', STR_PAD_LEFT);
        $customer = Customer::firstOrCreate(
            ['customer_id' => "P{$paddedId}"],
            [
                'name'     => "テスト患者{$i}",
                'store_id' => $stores->random()->id,
            ]
        );

        // 診療履歴（各患者1〜3件）
        collect(range(1, rand(1, 3)))->each(function () use ($customer, $types, $areaOptions) {
            $type = $types[array_rand($types)];
            // 全顎・部位なし系は treatment_area = null
            $noAreaTypes = ['オフィスホワイトニング', 'マウスピース矯正', '初診検査', '定期健診'];
            $area = in_array($type, $noAreaTypes) ? null : $areaOptions[array_rand($areaOptions)];

            TreatmentHistory::create([
                'customer_id'    => $customer->id,
                'treatment_type' => $type,
                'treatment_area' => $area,
                'treated_at'     => now()->subDays(rand(1, 180)),
                'staff'          => collect(['佐藤先生', '田中先生', '鈴木先生'])->random(),
            ]);
        });
    });
}
```

```bash
php artisan db:seed --class=CustomerListSeeder
```

---

### 2. CustomerController#index

**`app/Http/Controllers/CustomerController.php`**

```php
public function index(Request $request): View
{
    $query = Customer::with('store')->latest();

    if ($request->filled('customer_id')) {
        $query->where('customer_id', 'like', "%{$request->customer_id}%");
    }

    if ($request->filled('store_id')) {
        $query->where('store_id', $request->store_id);
    }

    if ($request->filled('treatment_type')) {
        $query->whereHas('treatmentHistories', fn($q) =>
            $q->where('treatment_type', 'like', "%{$request->treatment_type}%")
        );
    }

    if ($request->filled('treatment_area')) {
        $query->whereHas('treatmentHistories', fn($q) =>
            $q->where('treatment_area', 'like', "%{$request->treatment_area}%")
        );
    }

    $customers = $query->paginate(15)->withQueryString();
    $stores    = Store::orderBy('store_code')->get();

    return view('customers.index', compact('customers', 'stores'));
}
```

---

### 3. Bladeビュー

**`resources/views/customers/index.blade.php`**

```html
@extends('layouts.app')

@section('title', '顧客一覧')

@section('content')
<h2 class="text-xl font-bold text-gray-800 mb-4">患者一覧</h2>

<div class="flex gap-6">

    {{-- 左: 検索フィルタ --}}
    <div class="w-1/4">
        <x-card>
            <form method="GET" action="{{ route('customers.index') }}">
                <h3 class="font-semibold text-gray-700 mb-4">絞り込み</h3>

                <div class="mb-4">
                    <label class="block text-sm text-gray-600 mb-1">患者ID</label>
                    <input type="text" name="customer_id" value="{{ request('customer_id') }}"
                           class="w-full border rounded-lg px-3 py-2 text-base"
                           placeholder="P001">
                </div>

                <div class="mb-4">
                    <label class="block text-sm text-gray-600 mb-1">院</label>
                    <select name="store_id" class="w-full border rounded-lg px-3 py-2 text-base">
                        <option value="">すべて</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}"
                                {{ request('store_id') == $store->id ? 'selected' : '' }}>
                                {{ $store->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-sm text-gray-600 mb-1">治療内容</label>
                    <input type="text" name="treatment_type" value="{{ request('treatment_type') }}"
                           class="w-full border rounded-lg px-3 py-2 text-base"
                           placeholder="根管治療">
                </div>

                <div class="mb-6">
                    <label class="block text-sm text-gray-600 mb-1">治療部位</label>
                    <input type="text" name="treatment_area" value="{{ request('treatment_area') }}"
                           class="w-full border rounded-lg px-3 py-2 text-base"
                           placeholder="右上7番">
                </div>

                <x-button type="submit" class="w-full">検索</x-button>

                @if(request()->hasAny(['customer_id', 'store_id', 'treatment_type', 'treatment_area']))
                    <a href="{{ route('customers.index') }}"
                       class="block text-center text-sm text-gray-500 mt-3 hover:underline">
                        クリア
                    </a>
                @endif
            </form>
        </x-card>
    </div>

    {{-- 右: 顧客リスト --}}
    <div class="w-3/4">
        <p class="text-sm text-gray-500 mb-3">{{ $customers->total() }}件</p>

        @forelse($customers as $customer)
            <a href="{{ route('customers.show', $customer) }}"
               class="block mb-3 hover:shadow-md transition-shadow">
                <x-card>
                    <div class="flex justify-between items-center">
                        <div>
                            <span class="font-semibold text-lg">{{ $customer->name }}</span>
                            <span class="text-gray-400 text-sm ml-2">{{ $customer->customer_id }}</span>
                        </div>
                        <span class="text-gray-500 text-sm">{{ $customer->store->name ?? '-' }}</span>
                    </div>
                </x-card>
            </a>
        @empty
            <x-card>
                <p class="text-gray-400 text-center py-8">顧客が見つかりませんでした</p>
            </x-card>
        @endforelse

        <div class="mt-4">
            {{ $customers->links() }}
        </div>
    </div>

</div>
@endsection
```

---

## 確認手順（単体）

```bash
# 1. Seederでデータ投入（20件の顧客・店舗・施術履歴）
php artisan db:seed --class=CustomerListSeeder

# 2. サーバー起動
php artisan serve
npm run dev
```

ブラウザで確認:
1. `http://localhost:8000/customers` にアクセス → 患者カードが表示される
2. 患者ID欄に `P001` と入力して検索 → 1件に絞られる
3. 院セレクトで「渋谷歯科クリニック」を選択して検索 → その院の患者のみ表示される
4. 治療内容に `根管` と入力して検索 → 根管治療履歴のある患者のみ表示される
5. 治療部位に `右上` と入力して検索 → 右上の歯を治療した患者のみ表示される
6. 治療内容 `虫歯` + 治療部位 `右上` でAND検索 → 両方の条件に合う患者のみ表示される
5. 「クリア」リンクをクリック → 全件表示に戻る
6. ページネーション（15件超える場合）が表示・動作する

---

## 完了条件

- [ ] `/customers` にアクセスすると患者一覧が表示される
- [ ] 患者IDで部分一致検索ができる
- [ ] 院セレクトで絞り込みができる
- [ ] 治療内容（treatment_type）で部分一致検索ができる
- [ ] 治療部位（treatment_area）で部分一致検索ができる
- [ ] 治療内容と治療部位のAND検索が動作する
- [ ] 全4条件の組み合わせAND検索が動作する
- [ ] 「クリア」で検索条件がリセットされる
- [ ] ページネーションが表示され、ページを移動しても検索条件が維持される
- [ ] 患者カードをタップすると患者詳細画面（`/customers/{id}`）に遷移する
- [ ] 検索結果0件の場合に「患者が見つかりませんでした」が表示される
