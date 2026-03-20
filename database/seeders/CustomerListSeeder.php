<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Store;
use App\Models\TreatmentHistory;
use Illuminate\Database\Seeder;

class CustomerListSeeder extends Seeder
{
    public function run(): void
    {
        $stores = collect([
            ['store_code' => 'C001', 'name' => '渋谷歯科クリニック'],
            ['store_code' => 'C002', 'name' => '新宿歯科クリニック'],
            ['store_code' => 'C003', 'name' => '池袋歯科クリニック'],
        ])->map(fn($data) => Store::firstOrCreate(['store_code' => $data['store_code']], $data));

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

            collect(range(1, rand(1, 3)))->each(function () use ($customer, $types, $areaOptions) {
                $type = $types[array_rand($types)];
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
}
