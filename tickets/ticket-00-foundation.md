# TICKET-00: 共通基盤

← [一覧に戻る](./README.md)

**ステータス**: ⬜ 未着手

**概要**: 全画面チケットが依存する共通基盤を構築する。Migration・Eloquentモデル・レイアウト・Bladeコンポーネントが対象。このチケット完了後、各画面チケットを独立して進められる。

> **ドメイン**: 歯科医院の患者・診療履歴管理システム。DBカラム名は汎用名（customer_id, store_id, treatment_area）を使用し、UI表示では「患者」「院」「治療部位」と表示する。

---

## 作業内容

### 1. Migration（6テーブル）

```bash
php artisan make:migration create_stores_table
php artisan make:migration create_customers_table
php artisan make:migration create_reservations_table
php artisan make:migration create_treatment_histories_table
php artisan make:migration create_import_jobs_table
php artisan make:migration create_import_errors_table
```

#### stores
```php
$table->id();
$table->string('store_code')->unique();
$table->string('name');
$table->timestamps();
```

#### customers
```php
$table->id();
$table->string('customer_id')->unique(); // C001形式
$table->string('name');
$table->date('birth_date')->nullable();
$table->string('phone')->nullable();
$table->foreignId('store_id')->constrained()->cascadeOnDelete();
$table->timestamps();
```

#### reservations
```php
$table->id();
$table->string('reservation_id')->unique(); // R001形式
$table->foreignId('customer_id')->constrained()->cascadeOnDelete();
$table->dateTime('reserved_at');
$table->string('staff')->nullable();
$table->string('status')->default('reserved'); // reserved / completed / cancelled
$table->timestamps();
```

#### treatment_histories
```php
$table->id();
$table->foreignId('customer_id')->constrained()->cascadeOnDelete();
$table->foreignId('reservation_id')->nullable()->constrained()->nullOnDelete();
$table->dateTime('treated_at');
$table->string('treatment_type');           // 治療内容: 虫歯治療、根管治療 等
$table->string('treatment_area')->nullable(); // 治療部位: 右上7番、上顎前歯部 等（空欄あり）
$table->string('staff')->nullable();
$table->text('notes')->nullable();
$table->timestamps();
```

#### import_jobs
```php
$table->id();
$table->string('type'); // customers / reservations
$table->string('filename');
$table->string('status')->default('processing'); // processing / completed / failed
$table->integer('total_rows')->default(0);
$table->integer('success_rows')->default(0);
$table->integer('error_rows')->default(0);
$table->timestamps();
```

#### import_errors
```php
$table->id();
$table->foreignId('import_job_id')->constrained()->cascadeOnDelete();
$table->integer('row_number');
$table->json('row_data');
$table->text('error_message');
$table->timestamps();
```

---

### 2. Eloquentモデル（6個）

```bash
php artisan make:model Store
php artisan make:model Customer
php artisan make:model Reservation
php artisan make:model TreatmentHistory
php artisan make:model ImportJob
php artisan make:model ImportError
```

**リレーション定義（主要なもの）:**

```php
// Store
public function customers(): HasMany { return $this->hasMany(Customer::class); }

// Customer
public function store(): BelongsTo { return $this->belongsTo(Store::class); }
public function reservations(): HasMany { return $this->hasMany(Reservation::class); }
public function treatmentHistories(): HasMany { return $this->hasMany(TreatmentHistory::class); }

// TreatmentHistory
public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
public function reservation(): BelongsTo { return $this->belongsTo(Reservation::class); }

// Scope（患者詳細フィルタで使用）
public function scopeByType(Builder $query, string $type): Builder
{
    return $query->where('treatment_type', 'like', "%{$type}%");
}

public function scopeByArea(Builder $query, string $area): Builder
{
    return $query->where('treatment_area', 'like', "%{$area}%");
}

// ImportJob
public function errors(): HasMany { return $this->hasMany(ImportError::class); }
```

---

### 3. レイアウトテンプレート

**`resources/views/layouts/app.blade.php`**

```html
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', '顧客管理')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 min-h-screen flex">

    {{-- サイドバー --}}
    <nav class="w-1/5 bg-white border-r shadow-sm flex flex-col">
        <div class="p-4 border-b">
            <h1 class="text-lg font-bold text-blue-600">顧客管理</h1>
        </div>
        <ul class="flex-1 p-2">
            <li>
                <a href="{{ route('dashboard') }}"
                   class="flex items-center min-h-[44px] px-4 rounded-lg hover:bg-gray-100 text-gray-700">
                    ダッシュボード
                </a>
            </li>
            <li>
                <a href="{{ route('customers.index') }}"
                   class="flex items-center min-h-[44px] px-4 rounded-lg hover:bg-gray-100 text-gray-700">
                    患者一覧
                </a>
            </li>
            <li>
                <a href="{{ route('import.index') }}"
                   class="flex items-center min-h-[44px] px-4 rounded-lg hover:bg-gray-100 text-gray-700">
                    CSVインポート
                </a>
            </li>
            <li>
                <a href="{{ route('import.history') }}"
                   class="flex items-center min-h-[44px] px-4 rounded-lg hover:bg-gray-100 text-gray-700">
                    インポート履歴
                </a>
            </li>
        </ul>
    </nav>

    {{-- メインコンテンツ --}}
    <main class="w-4/5 p-6 overflow-y-auto">
        @if(session('success'))
            <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-lg">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mb-4 p-4 bg-red-100 text-red-800 rounded-lg">
                {{ session('error') }}
            </div>
        @endif

        @yield('content')
    </main>

</body>
</html>
```

---

### 4. Bladeコンポーネント（3個）

```bash
php artisan make:component Card --view
php artisan make:component Badge --view
php artisan make:component Button --view
```

**`resources/views/components/card.blade.php`**
```html
<div {{ $attributes->merge(['class' => 'bg-white rounded-xl shadow-sm p-4']) }}>
    {{ $slot }}
</div>
```

**`resources/views/components/badge.blade.php`**
```php
@php
$colorMap = [
    'reserved'  => 'bg-blue-100 text-blue-800',
    'completed' => 'bg-green-100 text-green-800',
    'cancelled' => 'bg-gray-100 text-gray-600',
];
$color = $colorMap[$status] ?? 'bg-gray-100 text-gray-600';
@endphp
<span class="inline-block px-2 py-1 rounded-full text-sm font-medium {{ $color }}">
    {{ $status }}
</span>
```

**`resources/views/components/button.blade.php`**
```html
<button {{ $attributes->merge(['class' => 'min-h-[44px] px-6 py-3 rounded-lg text-base font-medium bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50']) }}>
    {{ $slot }}
</button>
```

---

### 5. ルーティング骨格（`routes/web.php`）

```php
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ImportController;

Route::get('/', fn() => redirect()->route('dashboard'));

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');

Route::get('/import', [ImportController::class, 'index'])->name('import.index');
Route::post('/import/customers', [ImportController::class, 'importCustomers'])->name('import.customers');
Route::post('/import/reservations', [ImportController::class, 'importReservations'])->name('import.reservations');
Route::get('/import/history', [ImportController::class, 'history'])->name('import.history');
Route::get('/import/history/{importJob}/errors', [ImportController::class, 'errors'])->name('import.errors');
```

> **注意**: 各Controllerは対応する画面チケットで作成する。このチケット完了時点ではControllerファイルは存在しないため、ルート登録後にControllerのstubを先に作成しておく。

```bash
php artisan make:controller DashboardController
php artisan make:controller CustomerController
php artisan make:controller ImportController
```

各Controllerのメソッドは、対応する画面チケットで実装する。

---

### 6. インデックス定義（パフォーマンス用）

customersテーブルのmigrationに追加:
```php
$table->index('store_id');
$table->index('customer_id');
```

treatment_historiesテーブルのmigrationに追加:
```php
$table->index(['customer_id', 'treated_at']);
$table->index('treatment_type');
$table->index('treatment_area');
```

---

## 確認手順（単体）

```bash
# 1. マイグレーション実行
php artisan migrate

# 2. テーブルが作成されていることを確認
php artisan tinker
>>> \Schema::getColumnListing('customers')

# 3. Controllerのstubが存在することを確認
ls app/Http/Controllers/

# 4. ルートが登録されていることを確認
php artisan route:list

# 5. アプリ起動（別ターミナルで）
php artisan serve
npm run dev

# 6. ブラウザで http://localhost:8000 にアクセス
#    → 500エラーにならずにリダイレクトされればOK（Controllerが空なので404は許容）
```

---

## 完了条件

- [ ] `php artisan migrate` がエラーなく完了する
- [ ] 6テーブルがDBに作成される
- [ ] 6つのEloquentモデルが存在し、リレーションが定義されている
- [ ] `resources/views/layouts/app.blade.php` が存在する
- [ ] `<x-card>`, `<x-badge>`, `<x-button>` コンポーネントが存在する
- [ ] `php artisan route:list` で全ルートが確認できる
- [ ] `php artisan serve` でサーバーが起動する
