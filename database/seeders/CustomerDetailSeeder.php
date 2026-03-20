<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Reservation;
use App\Models\Store;
use App\Models\TreatmentHistory;
use Illuminate\Database\Seeder;

class CustomerDetailSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::firstOrCreate(
            ['store_code' => 'C001'],
            ['name' => '渋谷歯科クリニック']
        );

        $customer = Customer::firstOrCreate(
            ['customer_id' => 'P001'],
            [
                'name'       => '山田 太郎',
                'birth_date' => '1990-05-15',
                'phone'      => '090-1234-5678',
                'store_id'   => $store->id,
            ]
        );

        $histories = [
            ['treatment_type' => 'レジン充填（白い詰め物）', 'treatment_area' => '右上7番',  'treated_at' => now()->subDays(5),   'staff' => '佐藤先生'],
            ['treatment_type' => '根管治療（神経治療）',      'treatment_area' => '右上7番',  'treated_at' => now()->subDays(20),  'staff' => '佐藤先生'],
            ['treatment_type' => '抜髄（神経を抜く）',       'treatment_area' => '右上7番',  'treated_at' => now()->subDays(35),  'staff' => '佐藤先生'],
            ['treatment_type' => 'クリーニング（PMTC）',     'treatment_area' => '全顎',     'treated_at' => now()->subDays(60),  'staff' => '田中先生'],
            ['treatment_type' => '歯石除去（スケーリング）',  'treatment_area' => '上顎全体', 'treated_at' => now()->subDays(60),  'staff' => '田中先生'],
            ['treatment_type' => '虫歯治療',                'treatment_area' => '左下6番',  'treated_at' => now()->subDays(95),  'staff' => '鈴木先生'],
            ['treatment_type' => 'セラミックインレー',        'treatment_area' => '左下6番',  'treated_at' => now()->subDays(110), 'staff' => '鈴木先生'],
            ['treatment_type' => 'ジルコニアクラウン',        'treatment_area' => '右上6番',  'treated_at' => now()->subDays(150), 'staff' => '佐藤先生'],
            ['treatment_type' => 'オフィスホワイトニング',    'treatment_area' => null,       'treated_at' => now()->subDays(180), 'staff' => '田中先生'],
            ['treatment_type' => '親知らず抜歯',             'treatment_area' => '左下8番',  'treated_at' => now()->subDays(240), 'staff' => '田中先生'],
            ['treatment_type' => '歯周病治療',               'treatment_area' => '下顎全体', 'treated_at' => now()->subDays(300), 'staff' => '鈴木先生'],
            ['treatment_type' => '初診検査',                 'treatment_area' => null,       'treated_at' => now()->subDays(365), 'staff' => '佐藤先生'],
        ];

        foreach ($histories as $data) {
            $reservation = Reservation::create([
                'reservation_id' => 'R' . uniqid(),
                'customer_id'    => $customer->id,
                'reserved_at'    => $data['treated_at'],
                'staff'          => $data['staff'],
                'status'         => 'completed',
            ]);

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

        echo "URL: /customers/{$customer->id}\n";
    }
}
