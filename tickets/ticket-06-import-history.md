# TICKET-06: インポート履歴画面

← [一覧に戻る](./README.md)

**ステータス**: ⬜ 未着手

**依存**: TICKET-00（共通基盤）完了済みであること

**概要**: CSVインポートの処理結果一覧と、エラー行の詳細を確認できる画面。専用Seederでインポートジョブデータを直接投入するため、TICKET-05（CSVインポート実行）を完了していなくても単体で確認できる。

---

## 作業内容

### 1. 専用 Seeder（このチケット単体で確認するためのデータ）

```bash
php artisan make:seeder ImportHistorySeeder
```

```php
// database/seeders/ImportHistorySeeder.php
public function run(): void
{
    // 完了ジョブ（エラーなし）
    ImportJob::create([
        'type'         => 'customers',
        'filename'     => 'patients_2026-03-20.csv',
        'status'       => 'completed',
        'total_rows'   => 100,
        'success_rows' => 100,
        'error_rows'   => 0,
        'created_at'   => now()->subHours(1),
    ]);

    // 完了ジョブ（エラーあり）
    $jobWithErrors = ImportJob::create([
        'type'         => 'reservations',
        'filename'     => 'reservations_2026-03-19.csv', // 患者IDが存在しない行を含む
        'status'       => 'completed',
        'total_rows'   => 50,
        'success_rows' => 47,
        'error_rows'   => 3,
        'created_at'   => now()->subHours(25),
    ]);

    // エラー詳細（3件）
    $errorData = [
        [
            'row_number'    => 5,
            'row_data'      => ['reservation_id' => 'R999', 'patient_id' => 'P999', 'datetime' => '2026-03-19 10:00', 'doctor' => '山田先生', 'status' => 'reserved', 'treatment_area' => '虫歯治療'],
            'error_message' => '患者ID P999 が存在しません',
        ],
        [
            'row_number'    => 23,
            'row_data'      => ['reservation_id' => 'R998', 'patient_id' => 'P001', 'datetime' => 'invalid-date', 'doctor' => '田中先生', 'status' => 'reserved', 'treatment_area' => '根管治療'],
            'error_message' => '日付形式が不正です: invalid-date',
        ],
        [
            'row_number'    => 41,
            'row_data'      => ['reservation_id' => 'R997', 'patient_id' => 'P002', 'datetime' => '2026-03-19 14:00', 'doctor' => '佐藤先生', 'status' => 'unknown', 'treatment_area' => '歯石除去'],
            'error_message' => '不正なステータス: unknown',
        ],
    ];

    foreach ($errorData as $data) {
        ImportError::create(array_merge(['import_job_id' => $jobWithErrors->id], $data));
    }

    // 処理中ジョブ（まだ実行中の状態）
    ImportJob::create([
        'type'     => 'customers',
        'filename' => 'patients_2026-03-20_v2.csv',
        'status'   => 'processing',
        'total_rows'   => 0,
        'success_rows' => 0,
        'error_rows'   => 0,
        'created_at'   => now()->subMinutes(2),
    ]);
}
```

```bash
php artisan db:seed --class=ImportHistorySeeder
```

---

### 2. ImportController（履歴・エラー詳細部分）

TICKET-05で作成した `ImportController` の `history` と `errors` メソッドを実装する:

```php
public function history(): View
{
    $jobs = ImportJob::orderByDesc('created_at')->paginate(10);
    return view('import.history', compact('jobs'));
}

public function errors(ImportJob $importJob): View
{
    $errors = $importJob->errors()->paginate(20);
    return view('import.errors', compact('importJob', 'errors'));
}
```

---

### 3. Bladeビュー（履歴一覧）

**`resources/views/import/history.blade.php`**

```html
@extends('layouts.app')

@section('title', 'インポート履歴')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h2 class="text-xl font-bold text-gray-800">インポート履歴</h2>
    <a href="{{ route('import.index') }}">
        <x-button>CSVをインポート</x-button>
    </a>
</div>

@forelse($jobs as $job)
    <x-card class="mb-4">
        <div class="flex justify-between items-start">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <span class="font-semibold text-gray-800">{{ $job->filename }}</span>
                    <span class="px-2 py-0.5 rounded text-xs
                        {{ $job->type === 'customers' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700' }}">
                        {{ $job->type === 'customers' ? '顧客CSV' : '予約CSV' }}
                    </span>
                </div>
                <p class="text-sm text-gray-500">{{ $job->created_at->format('Y/m/d H:i') }}</p>
            </div>

            <div class="text-right">
                {{-- ステータスバッジ --}}
                @if($job->status === 'completed')
                    <span class="inline-block px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-medium mb-2">
                        完了
                    </span>
                @elseif($job->status === 'processing')
                    <span class="inline-block px-3 py-1 bg-yellow-100 text-yellow-700 rounded-full text-sm font-medium mb-2">
                        処理中
                    </span>
                @else
                    <span class="inline-block px-3 py-1 bg-red-100 text-red-700 rounded-full text-sm font-medium mb-2">
                        失敗
                    </span>
                @endif

                {{-- 処理結果数 --}}
                @if($job->status !== 'processing')
                    <p class="text-sm text-gray-600">
                        全 {{ $job->total_rows }}件 /
                        <span class="text-green-600">成功 {{ $job->success_rows }}件</span>
                        @if($job->error_rows > 0)
                            / <span class="text-red-600">エラー {{ $job->error_rows }}件</span>
                        @endif
                    </p>
                    @if($job->error_rows > 0)
                        <a href="{{ route('import.errors', $job) }}"
                           class="text-sm text-red-600 hover:underline mt-1 inline-block">
                            エラー詳細を確認 →
                        </a>
                    @endif
                @endif
            </div>
        </div>
    </x-card>
@empty
    <x-card>
        <p class="text-gray-400 text-center py-8">インポート履歴がありません</p>
    </x-card>
@endforelse

<div class="mt-4">
    {{ $jobs->links() }}
</div>
@endsection
```

---

### 4. Bladeビュー（エラー詳細）

**`resources/views/import/errors.blade.php`**

```html
@extends('layouts.app')

@section('title', 'エラー詳細')

@section('content')
<a href="{{ route('import.history') }}"
   class="inline-flex items-center text-blue-600 hover:underline mb-4 text-sm">
    ← インポート履歴に戻る
</a>

<x-card class="mb-6">
    <h2 class="text-lg font-bold text-gray-800 mb-2">{{ $importJob->filename }}</h2>
    <p class="text-sm text-gray-500">
        {{ $importJob->created_at->format('Y/m/d H:i') }} /
        全 {{ $importJob->total_rows }}件 /
        <span class="text-green-600">成功 {{ $importJob->success_rows }}件</span> /
        <span class="text-red-600">エラー {{ $importJob->error_rows }}件</span>
    </p>
</x-card>

<h3 class="font-semibold text-gray-700 mb-4">エラー行一覧</h3>

@foreach($errors as $error)
    <x-card class="mb-3 border-l-4 border-red-400">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-sm font-semibold text-red-600 mb-1">{{ $error->row_number }}行目</p>
                <p class="text-red-700 text-sm mb-2">{{ $error->error_message }}</p>
                <div class="text-xs text-gray-500 bg-gray-50 rounded p-2 font-mono">
                    @foreach($error->row_data as $key => $value)
                        <span class="mr-3"><span class="text-gray-400">{{ $key }}:</span> {{ $value ?: '（空）' }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    </x-card>
@endforeach

<div class="mt-4">
    {{ $errors->links() }}
</div>
@endsection
```

---

## 確認手順（単体）

```bash
# 1. Seederでインポートジョブデータを直接投入
php artisan db:seed --class=ImportHistorySeeder

# 2. サーバー起動
php artisan serve
npm run dev
```

ブラウザで確認:
1. `http://localhost:8000/import/history` にアクセス → 3件の履歴が表示される
2. 「完了」バッジ（緑）・「処理中」バッジ（黄）が表示される
3. エラーありのジョブ（reservations_2026-03-19.csv）に「エラー詳細を確認 →」リンクが表示される
4. 「エラー詳細を確認 →」をタップ → エラー詳細画面が表示される
5. エラー詳細画面に3件のエラー行（行番号・エラーメッセージ・元データ）が表示される
6. 「インポート履歴に戻る」で履歴画面に戻れる
7. 「CSVをインポート」ボタンでインポート画面に遷移できる

---

## 完了条件

- [ ] `/import/history` にアクセスするとインポート履歴一覧が表示される
- [ ] 各ジョブのファイル名・種別（顧客/予約）・日時が表示される
- [ ] ステータス（完了/処理中/失敗）がバッジで色分けされて表示される
- [ ] 処理結果数（全件・成功・エラー）が表示される
- [ ] エラーがあるジョブに「エラー詳細を確認」リンクが表示される
- [ ] エラー詳細画面でエラー行の行番号・エラーメッセージ・元データが確認できる
- [ ] 履歴が0件の場合に「インポート履歴がありません」が表示される
- [ ] ページネーションが動作する（10件超の場合）
