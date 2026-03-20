<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\ImportJob;
use App\Models\Store;
use App\Models\TreatmentHistory;
use Illuminate\Database\Seeder;

class DashboardSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::firstOrCreate(
            ['store_code' => 'C001'],
            ['name' => '渋谷歯科クリニック']
        );

        $customers = collect(range(1, 5))->map(fn($i) => Customer::firstOrCreate(
            ['customer_id' => "P00{$i}"],
            [
                'name'     => "テスト患者{$i}",
                'store_id' => $store->id,
            ]
        ));

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
}
