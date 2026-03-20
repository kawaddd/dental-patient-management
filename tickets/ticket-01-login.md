# TICKET-01: ログイン画面

← [一覧に戻る](./README.md)

**ステータス**: ⬜ 未着手

**依存**: TICKET-00（共通基盤）完了済みであること

**概要**: Laravel Breezeを導入してログイン画面を実装する。ログイン後はダッシュボードにリダイレクトする。

---

## 作業内容

### 1. Laravel Breeze インストール

```bash
composer require laravel/breeze --dev
php artisan breeze:install blade
npm install
npm run build
```

---

### 2. レイアウトの統合

Breezeが生成する `resources/views/layouts/guest.blade.php` を、TICKET-00で作成したデザインに合わせて調整する。

**`resources/views/layouts/guest.blade.php`** を以下に書き換える:

```html
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ログイン - 顧客管理</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md">
        <h1 class="text-2xl font-bold text-blue-600 text-center mb-6">顧客管理システム</h1>
        {{ $slot }}
    </div>
</body>
</html>
```

---

### 3. ログイン後のリダイレクト先を変更

**`app/Http/Controllers/Auth/AuthenticatedSessionController.php`** の `store` メソッドで、ログイン後のリダイレクト先が `dashboard` になっていることを確認する（Breezeのデフォルトで `RouteServiceProvider::HOME` が使われる）。

**`app/Providers/RouteServiceProvider.php`** または **`routes/web.php`**:
```php
// Breezeのリダイレクト先
public const HOME = '/dashboard';
```

---

### 4. ダッシュボードルートへの認証ミドルウェア適用

TICKET-00で追加したルートに `auth` ミドルウェアを適用する:

```php
// routes/web.php
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
    Route::get('/import', [ImportController::class, 'index'])->name('import.index');
    Route::post('/import/customers', [ImportController::class, 'importCustomers'])->name('import.customers');
    Route::post('/import/reservations', [ImportController::class, 'importReservations'])->name('import.reservations');
    Route::get('/import/history', [ImportController::class, 'history'])->name('import.history');
    Route::get('/import/history/{importJob}/errors', [ImportController::class, 'errors'])->name('import.errors');
});
```

---

### 5. テストユーザー Seeder

```bash
php artisan make:seeder TestUserSeeder
```

```php
// database/seeders/TestUserSeeder.php
public function run(): void
{
    User::updateOrCreate(
        ['email' => 'admin@example.com'],
        [
            'name'     => '管理者',
            'password' => Hash::make('password'),
        ]
    );
}
```

```bash
php artisan db:seed --class=TestUserSeeder
```

---

## 確認手順（単体）

```bash
# 1. Breezeインストール後にビルド
npm run build

# 2. テストユーザー作成
php artisan db:seed --class=TestUserSeeder

# 3. サーバー起動
php artisan serve
```

ブラウザで確認:
1. `http://localhost:8000/login` にアクセス → ログイン画面が表示される
2. `admin@example.com` / `password` でログインできる
3. ログイン後 `/dashboard` にリダイレクトされる（ダッシュボード未実装なら404でOK）
4. 未ログイン状態で `/customers` にアクセス → `/login` にリダイレクトされる

---

## 完了条件

- [ ] `/login` にアクセスするとログイン画面が表示される
- [ ] 正しいメールアドレス・パスワードでログインできる
- [ ] ログイン後 `/dashboard` にリダイレクトされる
- [ ] 未認証状態で保護されたルートにアクセスすると `/login` にリダイレクトされる
- [ ] 間違ったパスワードでログインするとエラーメッセージが表示される
