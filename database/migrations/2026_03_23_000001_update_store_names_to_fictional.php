<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('stores')->where('store_code', 'C001')->update(['name' => '佐藤歯科クリニック']);
        DB::table('stores')->where('store_code', 'C002')->update(['name' => '鈴木歯科クリニック']);
        DB::table('stores')->where('store_code', 'C003')->update(['name' => '高橋歯科クリニック']);
        DB::table('stores')->where('store_code', 'C004')->update(['name' => '田中歯科クリニック']);
        DB::table('stores')->where('store_code', 'C005')->update(['name' => '渡辺歯科クリニック']);
    }

    public function down(): void
    {
        DB::table('stores')->where('store_code', 'C001')->update(['name' => '渋谷セントラル歯科']);
        DB::table('stores')->where('store_code', 'C002')->update(['name' => '新宿ファミリー歯科']);
        DB::table('stores')->where('store_code', 'C003')->update(['name' => '池袋マイ歯科クリニック']);
        DB::table('stores')->where('store_code', 'C004')->update(['name' => '品川ホワイト歯科']);
        DB::table('stores')->where('store_code', 'C005')->update(['name' => '吉祥寺ひまわり歯科']);
    }
};
