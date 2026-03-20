# TICKET-05: CSVインポート画面

← [一覧に戻る](./README.md)

**ステータス**: ⬜ 未着手

**依存**: TICKET-00（共通基盤）完了済みであること

**概要**: 顧客CSV・予約CSVのアップロード画面。タブ切り替えでCSV種別を選択し、ファイルを選択してインポートを実行する。

---

## 作業内容

### 1. テスト用CSVファイル（このチケット単体で確認するためのデータ）

動作確認用のサンプルCSVを用意する。

**`storage/app/samples/patients_sample.csv`**:
```csv
patient_id,name,birth_date,phone,clinic_code
P001,山田太郎,1990-05-15,090-1234-5678,C001
P002,佐藤花子,1985-08-22,080-9876-5432,C001
P003,田中次郎,1995-03-10,,C002
```

**`storage/app/samples/reservations_sample.csv`**:
```csv
reservation_id,patient_id,datetime,doctor,status,treatment_area
R001,P001,2026-03-20 10:00,佐藤先生,completed,虫歯治療
R002,P002,2026-03-20 14:00,田中先生,reserved,歯石除去・クリーニング
R003,P001,2026-03-18 11:00,鈴木先生,completed,根管治療
```

※ これらはドキュメント用。実際の確認はブラウザからCSVをアップロードして行う。

---

### 2. CSVインポートサービス

```bash
mkdir -p app/Services
```

**`app/Services/CustomerImportService.php`**:

```php
<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\ImportError;
use App\Models\ImportJob;
use App\Models\Store;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\LazyCollection;

class CustomerImportService
{
    public function handle(UploadedFile $file): ImportJob
    {
        $job = ImportJob::create([
            'type'     => 'customers',
            'filename' => $file->getClientOriginalName(),
            'status'   => 'processing',
        ]);

        $totalRows   = 0;
        $successRows = 0;
        $errorRows   = 0;

        LazyCollection::make(function () use ($file) {
            $handle = fopen($file->getRealPath(), 'r');
            $header = fgetcsv($handle); // ヘッダー行をスキップ
            while (($row = fgetcsv($handle)) !== false) {
                yield array_combine($header, $row);
            }
            fclose($handle);
        })->each(function ($row) use ($job, &$totalRows, &$successRows, &$errorRows) {
            $totalRows++;

            try {
                // store_code から store_id を解決（なければ自動作成）
                $store = Store::firstOrCreate(
                    ['store_code' => $row['store_code']],
                    ['name'       => $row['store_code']] // 名称不明のためstoreCodeで仮登録
                );

                Customer::updateOrCreate(
                    ['customer_id' => $row['customer_id']],
                    [
                        'name'       => $row['name'],
                        'birth_date' => $row['birth_date'] ?: null,
                        'phone'      => $row['phone'] ?: null,
                        'store_id'   => $store->id,
                    ]
                );

                $successRows++;
            } catch (\Exception $e) {
                $errorRows++;
                ImportError::create([
                    'import_job_id' => $job->id,
                    'row_number'    => $totalRows,
                    'row_data'      => $row,
                    'error_message' => $e->getMessage(),
                ]);
            }
        });

        $job->update([
            'status'       => 'completed',
            'total_rows'   => $totalRows,
            'success_rows' => $successRows,
            'error_rows'   => $errorRows,
        ]);

        return $job;
    }
}
```

**`app/Services/ReservationImportService.php`**:

```php
<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\ImportError;
use App\Models\ImportJob;
use App\Models\Reservation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\LazyCollection;

class ReservationImportService
{
    public function handle(UploadedFile $file): ImportJob
    {
        $job = ImportJob::create([
            'type'     => 'reservations',
            'filename' => $file->getClientOriginalName(),
            'status'   => 'processing',
        ]);

        $totalRows   = 0;
        $successRows = 0;
        $errorRows   = 0;

        LazyCollection::make(function () use ($file) {
            $handle = fopen($file->getRealPath(), 'r');
            $header = fgetcsv($handle);
            while (($row = fgetcsv($handle)) !== false) {
                yield array_combine($header, $row);
            }
            fclose($handle);
        })->each(function ($row) use ($job, &$totalRows, &$successRows, &$errorRows) {
            $totalRows++;

            try {
                $customer = Customer::where('customer_id', $row['customer_id'])->first();

                if (!$customer) {
                    throw new \Exception("顧客ID {$row['customer_id']} が存在しません");
                }

                if (!in_array($row['status'], ['reserved', 'completed', 'cancelled'])) {
                    throw new \Exception("不正なステータス: {$row['status']}");
                }

                $reservation = Reservation::updateOrCreate(
                    ['reservation_id' => $row['reservation_id']],
                    [
                        'customer_id' => $customer->id,
                        'reserved_at' => $row['datetime'],
                        'staff'       => $row['doctor'] ?: null,
                        'status'      => $row['status'],
                    ]
                );

                // 予約CSVに治療情報が含まれる場合は診療履歴も登録
                if (!empty($row['treatment_type'])) {
                    TreatmentHistory::updateOrCreate(
                        [
                            'reservation_id' => $reservation->id,
                        ],
                        [
                            'customer_id'    => $customer->id,
                            'treated_at'     => $row['datetime'],
                            'treatment_type' => $row['treatment_type'],
                            'treatment_area' => $row['treatment_area'] ?: null,
                            'staff'          => $row['doctor'] ?: null,
                        ]
                    );
                }

                $successRows++;
            } catch (\Exception $e) {
                $errorRows++;
                ImportError::create([
                    'import_job_id' => $job->id,
                    'row_number'    => $totalRows,
                    'row_data'      => $row,
                    'error_message' => $e->getMessage(),
                ]);
            }
        });

        $job->update([
            'status'       => 'completed',
            'total_rows'   => $totalRows,
            'success_rows' => $successRows,
            'error_rows'   => $errorRows,
        ]);

        return $job;
    }
}
```

---

### 3. FormRequest バリデーション

```bash
php artisan make:request ImportCsvRequest
```

```php
// app/Http/Requests/ImportCsvRequest.php
public function rules(): array
{
    return [
        'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'], // 5MB
    ];
}

public function messages(): array
{
    return [
        'csv_file.required' => 'CSVファイルを選択してください',
        'csv_file.mimes'    => 'CSV形式のファイルを選択してください',
        'csv_file.max'      => 'ファイルサイズは5MB以下にしてください',
    ];
}
```

---

### 4. ImportController（インポート実行部分）

**`app/Http/Controllers/ImportController.php`**:

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportCsvRequest;
use App\Services\CustomerImportService;
use App\Services\ReservationImportService;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ImportController extends Controller
{
    public function __construct(
        private CustomerImportService    $customerImportService,
        private ReservationImportService $reservationImportService,
    ) {}

    public function index(): View
    {
        return view('import.index');
    }

    public function importCustomers(ImportCsvRequest $request): RedirectResponse
    {
        $job = $this->customerImportService->handle($request->file('csv_file'));

        $message = "インポート完了: 成功 {$job->success_rows}件";
        if ($job->error_rows > 0) {
            $message .= " / エラー {$job->error_rows}件";
        }

        return redirect()->route('import.history')->with('success', $message);
    }

    public function importReservations(ImportCsvRequest $request): RedirectResponse
    {
        $job = $this->reservationImportService->handle($request->file('csv_file'));

        $message = "インポート完了: 成功 {$job->success_rows}件";
        if ($job->error_rows > 0) {
            $message .= " / エラー {$job->error_rows}件";
        }

        return redirect()->route('import.history')->with('success', $message);
    }

    // history / errors メソッドは TICKET-06 で実装
    public function history(): View
    {
        return view('import.history', ['jobs' => collect()]);
    }

    public function errors(): View
    {
        abort(404);
    }
}
```

---

### 5. Bladeビュー

**`resources/views/import/index.blade.php`**

```html
@extends('layouts.app')

@section('title', 'CSVインポート')

@section('content')
<h2 class="text-xl font-bold text-gray-800 mb-6">CSVインポート</h2>

<div x-data="{ tab: 'customers', filename: '' }">

    {{-- タブ切り替え --}}
    <div class="flex border-b mb-6">
        <button @click="tab = 'customers'"
                :class="tab === 'customers' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-500'"
                class="px-6 py-3 text-base font-medium min-h-[44px]">
            顧客CSV
        </button>
        <button @click="tab = 'reservations'"
                :class="tab === 'reservations' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-500'"
                class="px-6 py-3 text-base font-medium min-h-[44px]">
            予約CSV
        </button>
    </div>

    {{-- 顧客CSVタブ --}}
    <div x-show="tab === 'customers'">
        <x-card class="max-w-2xl">
            <form method="POST" action="{{ route('import.customers') }}" enctype="multipart/form-data"
                  x-data="{ loading: false }" @submit="loading = true">
                @csrf

                {{-- バリデーションエラー --}}
                @if($errors->any())
                    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                        @foreach($errors->all() as $error)
                            <p class="text-red-600 text-sm">{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                {{-- ドロップゾーン --}}
                <div class="border-2 border-dashed border-gray-300 rounded-xl p-10 text-center mb-6
                            hover:border-blue-400 transition-colors"
                     @dragover.prevent
                     @drop.prevent="
                         $el.querySelector('input[type=file]').files = $event.dataTransfer.files;
                         filename = $event.dataTransfer.files[0]?.name ?? '';
                     ">
                    <p class="text-gray-500 mb-3">ここにCSVをドロップ</p>
                    <p class="text-gray-400 text-sm mb-4">または</p>
                    <label class="cursor-pointer">
                        <span class="px-4 py-2 bg-gray-100 rounded-lg text-sm hover:bg-gray-200">
                            ファイルを選択
                        </span>
                        <input type="file" name="csv_file" accept=".csv,.txt" class="hidden"
                               @change="filename = $event.target.files[0]?.name ?? ''">
                    </label>
                    <p x-show="filename" x-text="'選択: ' + filename"
                       class="mt-3 text-blue-600 text-sm"></p>
                </div>

                <x-button type="submit" :disabled="loading" class="w-full">
                    <span x-show="!loading">インポート実行</span>
                    <span x-show="loading">処理中...</span>
                </x-button>
            </form>
        </x-card>
    </div>

    {{-- 予約CSVタブ --}}
    <div x-show="tab === 'reservations'">
        <x-card class="max-w-2xl">
            <form method="POST" action="{{ route('import.reservations') }}" enctype="multipart/form-data"
                  x-data="{ loading: false }" @submit="loading = true">
                @csrf

                @if($errors->any())
                    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                        @foreach($errors->all() as $error)
                            <p class="text-red-600 text-sm">{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <div class="border-2 border-dashed border-gray-300 rounded-xl p-10 text-center mb-6
                            hover:border-blue-400 transition-colors"
                     @dragover.prevent
                     @drop.prevent="
                         $el.querySelector('input[type=file]').files = $event.dataTransfer.files;
                         filename = $event.dataTransfer.files[0]?.name ?? '';
                     ">
                    <p class="text-gray-500 mb-3">ここにCSVをドロップ</p>
                    <p class="text-gray-400 text-sm mb-4">または</p>
                    <label class="cursor-pointer">
                        <span class="px-4 py-2 bg-gray-100 rounded-lg text-sm hover:bg-gray-200">
                            ファイルを選択
                        </span>
                        <input type="file" name="csv_file" accept=".csv,.txt" class="hidden"
                               @change="filename = $event.target.files[0]?.name ?? ''">
                    </label>
                    <p x-show="filename" x-text="'選択: ' + filename"
                       class="mt-3 text-blue-600 text-sm"></p>
                </div>

                <x-button type="submit" :disabled="loading" class="w-full">
                    <span x-show="!loading">インポート実行</span>
                    <span x-show="loading">処理中...</span>
                </x-button>
            </form>
        </x-card>
    </div>

</div>
@endsection
```

**注意**: Alpine.jsが必要。`resources/js/app.js` に追加:
```bash
npm install alpinejs
```
```js
// resources/js/app.js
import Alpine from 'alpinejs'
window.Alpine = Alpine
Alpine.start()
```

---

## 確認手順（単体）

```bash
# 1. Alpine.jsをインストール・ビルド
npm install alpinejs
npm run dev
```

ブラウザで確認:
1. `http://localhost:8000/import` にアクセス → インポート画面が表示される
2. 「顧客CSV」「予約CSV」タブが切り替えられる
3. 「ファイルを選択」ボタンでCSVを選択 → ファイル名が表示される
4. 顧客CSVで `customers_sample.csv` をアップロード → インポート履歴画面にリダイレクトされてフラッシュメッセージが表示される
5. DBに顧客データが取り込まれていることを `php artisan tinker` で確認:
   ```
   >>> Customer::count()
   >>> Customer::first()
   ```
6. ファイルを選択せずに送信 → 「CSVファイルを選択してください」エラーが表示される
7. テキストファイル（.txt以外）を送信 → バリデーションエラーが表示される

---

## 完了条件

- [ ] `/import` にアクセスするとインポート画面が表示される
- [ ] 「顧客CSV」「予約CSV」タブが切り替えられる
- [ ] ファイル選択後にファイル名がUI上に表示される
- [ ] 顧客CSVをアップロードするとDBにデータが取り込まれる
- [ ] 予約CSVをアップロードするとDBにデータが取り込まれる（対応する顧客IDが存在する場合）
- [ ] インポート成功後にインポート履歴画面へリダイレクトされフラッシュメッセージが表示される
- [ ] ファイル未選択で送信するとバリデーションエラーが表示される
- [ ] 5MBを超えるファイルはエラーになる
- [ ] 存在しない顧客IDの予約CSVはエラー行として記録される（成功行のみ取り込まれる）
