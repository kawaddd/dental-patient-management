<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\ImportJob;
use App\Models\ImportError;
use App\Models\Reservation;
use App\Models\Store;
use App\Models\TreatmentHistory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductionSampleSeeder extends Seeder
{
    public function run(): void
    {
        // 既存データを全削除（外部キー制約を一時無効化）
        DB::statement('SET session_replication_role = replica');
        ImportError::truncate();
        ImportJob::truncate();
        TreatmentHistory::truncate();
        Reservation::truncate();
        Customer::truncate();
        Store::truncate();
        DB::statement('SET session_replication_role = DEFAULT');

        // ── 院 ──────────────────────────────────────────
        $stores = collect([
            ['store_code' => 'C001', 'name' => '佐藤歯科クリニック'],
            ['store_code' => 'C002', 'name' => '鈴木歯科クリニック'],
            ['store_code' => 'C003', 'name' => '高橋歯科クリニック'],
            ['store_code' => 'C004', 'name' => '田中歯科クリニック'],
            ['store_code' => 'C005', 'name' => '渡辺歯科クリニック'],
        ])->map(fn($d) => Store::create($d));

        // ── 患者データ ───────────────────────────────────
        $surnames = [
            '田中','鈴木','佐藤','高橋','伊藤','渡辺','山本','中村','小林','加藤',
            '吉田','山田','佐々木','山口','松本','井上','木村','林','斎藤','清水',
            '山崎','阿部','森','池田','橋本','石川','前田','岡田','藤田','後藤',
            '長谷川','石井','村上','近藤','坂本','遠藤','青木','藤井','西村','福田',
            '岡本','太田','松田','中島','浜田','原田','金子','平野','中野','野村',
        ];
        $surnameKana = [
            '田中'=>'タナカ','鈴木'=>'スズキ','佐藤'=>'サトウ','高橋'=>'タカハシ','伊藤'=>'イトウ',
            '渡辺'=>'ワタナベ','山本'=>'ヤマモト','中村'=>'ナカムラ','小林'=>'コバヤシ','加藤'=>'カトウ',
            '吉田'=>'ヨシダ','山田'=>'ヤマダ','佐々木'=>'ササキ','山口'=>'ヤマグチ','松本'=>'マツモト',
            '井上'=>'イノウエ','木村'=>'キムラ','林'=>'ハヤシ','斎藤'=>'サイトウ','清水'=>'シミズ',
            '山崎'=>'ヤマザキ','阿部'=>'アベ','森'=>'モリ','池田'=>'イケダ','橋本'=>'ハシモト',
            '石川'=>'イシカワ','前田'=>'マエダ','岡田'=>'オカダ','藤田'=>'フジタ','後藤'=>'ゴトウ',
            '長谷川'=>'ハセガワ','石井'=>'イシイ','村上'=>'ムラカミ','近藤'=>'コンドウ','坂本'=>'サカモト',
            '遠藤'=>'エンドウ','青木'=>'アオキ','藤井'=>'フジイ','西村'=>'ニシムラ','福田'=>'フクダ',
            '岡本'=>'オカモト','太田'=>'オオタ','松田'=>'マツダ','中島'=>'ナカジマ','浜田'=>'ハマダ',
            '原田'=>'ハラダ','金子'=>'カネコ','平野'=>'ヒラノ','中野'=>'ナカノ','野村'=>'ノムラ',
        ];
        $maleNames   = ['太郎','健太','翔','大輔','浩二','雄介','拓也','誠','直樹','和也','隆','光','洋介','慎一','博','正樹','健司','竜也','亮','浩'];
        $maleKana    = ['タロウ','ケンタ','ショウ','ダイスケ','コウジ','ユウスケ','タクヤ','マコト','ナオキ','カズヤ','タカシ','ヒカル','ヨウスケ','シンイチ','ヒロシ','マサキ','ケンジ','タツヤ','リョウ','ヒロシ'];
        $femaleNames = ['花子','愛','麻衣','彩','恵','千春','奈々','美穂','加奈','由美子','亜美','裕子','美咲','幸子','悦子','佳奈','里美','玲子','明日香','真由美'];
        $femaleKana  = ['ハナコ','アイ','マイ','アヤ','メグミ','チハル','ナナ','ミホ','カナ','ユミコ','アミ','ユウコ','ミサキ','サチコ','エツコ','カナ','サトミ','レイコ','アスカ','マユミ'];

        $notes = [
            null, null, null, null, null, null,
            'ゴールドアレルギーあり',
            '金属アレルギー（ニッケル）',
            '局所麻酔に過去アレルギー反応あり。投与前に確認必要',
            '高血圧のため治療前に血圧確認',
            '糖尿病あり。傷の治癒が遅い場合あり',
            '抗凝固薬（ワーファリン）服用中',
            '喘息持ち。使用材料に注意',
            '妊娠中（8ヶ月）。X線撮影不可',
            '骨粗しょう症のためビスホスホネート系薬剤服用中',
            '過去に根管治療で痛みが強かった。麻酔を十分に',
            '嘔吐反射強め。型取り時注意',
            '歯科恐怖症あり。ゆっくり丁寧な説明が必要',
            '義歯使用中（上顎部分床義歯）',
            '前回キャンセルあり。リマインド連絡推奨',
        ];

        $treatments = [
            ['type' => '初診検査',                    'area' => null],
            ['type' => '定期健診',                    'area' => null],
            ['type' => '虫歯治療',                    'area' => 'tooth'],
            ['type' => 'レジン充填（白い詰め物）',    'area' => 'tooth'],
            ['type' => 'インレー（金属詰め物）',      'area' => 'tooth'],
            ['type' => 'セラミックインレー',          'area' => 'tooth'],
            ['type' => '根管治療（神経治療）',        'area' => 'tooth'],
            ['type' => '抜髄（神経を抜く）',          'area' => 'tooth'],
            ['type' => '感染根管治療（再治療）',      'area' => 'tooth'],
            ['type' => '抜歯',                        'area' => 'tooth'],
            ['type' => '親知らず抜歯',               'area' => 'wisdom'],
            ['type' => 'クラウン（被せ物）',          'area' => 'tooth'],
            ['type' => 'セラミッククラウン',          'area' => 'tooth'],
            ['type' => 'ジルコニアクラウン',          'area' => 'tooth'],
            ['type' => 'ブリッジ',                   'area' => 'area'],
            ['type' => '部分入れ歯（局部義歯）',      'area' => 'area'],
            ['type' => '仮歯（テンポラリークラウン）', 'area' => 'tooth'],
            ['type' => '歯石除去（スケーリング）',    'area' => 'area'],
            ['type' => 'クリーニング（PMTC）',        'area' => null],
            ['type' => 'スケーリング・ルートプレーニング（SRP）', 'area' => 'area'],
            ['type' => '歯周病治療',                  'area' => 'area'],
            ['type' => 'フッ素塗布',                  'area' => null],
            ['type' => 'インプラント埋入',            'area' => 'tooth'],
            ['type' => 'インプラントメンテナンス',    'area' => 'tooth'],
            ['type' => 'マウスピース矯正',            'area' => null],
            ['type' => 'ワイヤー矯正',               'area' => null],
            ['type' => 'オフィスホワイトニング',      'area' => null],
            ['type' => 'ナイトガード（マウスピース）製作', 'area' => null],
            ['type' => '応急処置',                   'area' => 'tooth'],
            ['type' => 'レントゲン撮影',              'area' => null],
        ];

        $toothNumbers = [
            '右上1番','右上2番','右上3番','右上4番','右上5番','右上6番','右上7番','右上8番',
            '左上1番','左上2番','左上3番','左上4番','左上5番','左上6番','左上7番','左上8番',
            '右下1番','右下2番','右下3番','右下4番','右下5番','右下6番','右下7番','右下8番',
            '左下1番','左下2番','左下3番','左下4番','左下5番','左下6番','左下7番','左下8番',
        ];
        $areaOptions = ['上顎前歯部','下顎前歯部','上顎臼歯部','下顎臼歯部','上顎全体','下顎全体','全顎'];
        $wisdomOptions = ['右上8番','左上8番','右下8番','左下8番'];

        $staffByStore = [
            'C001' => ['田村先生','中野先生','松井先生'],
            'C002' => ['佐藤先生','高木先生','岩田先生'],
            'C003' => ['鈴木先生','橋本先生','西田先生'],
            'C004' => ['山田先生','加藤先生','石橋先生'],
            'C005' => ['伊藤先生','藤原先生','長田先生'],
        ];

        $reservationSeq = 1;

        for ($i = 1; $i <= 100; $i++) {
            $patientId = 'P' . str_pad($i, 3, '0', STR_PAD_LEFT);

            // 性別・名前
            $gender      = $i % 3 === 0 ? 'female' : 'male';
            $surname     = $surnames[array_rand($surnames)];
            $givenIdx    = array_rand($gender === 'female' ? $femaleNames : $maleNames);
            $givenName   = $gender === 'female' ? $femaleNames[$givenIdx] : $maleNames[$givenIdx];
            $givenKana   = $gender === 'female' ? $femaleKana[$givenIdx] : $maleKana[$givenIdx];
            $name        = $surname . ' ' . $givenName;
            $nameKana    = ($surnameKana[$surname] ?? $surname) . ' ' . $givenKana;

            // 生年月日（10歳〜85歳）
            $age       = rand(10, 85);
            $birthDate = now()->subYears($age)->subDays(rand(0, 364))->format('Y-m-d');

            // 電話番号（70%の確率で持つ）
            $phone = null;
            if (rand(1, 10) <= 7) {
                $prefixes = ['090','080','070','03','06'];
                $prefix   = $prefixes[array_rand($prefixes)];
                $phone    = $prefix . '-' . rand(1000, 9999) . '-' . rand(1000, 9999);
            }

            // メール（40%の確率で持つ）
            $email = null;
            if (rand(1, 10) <= 4) {
                $domains     = ['gmail.com','yahoo.co.jp','icloud.com','outlook.jp','docomo.ne.jp'];
                $localPart   = strtolower(preg_replace('/\s+/', '.', $surname)) . rand(10, 999);
                $email       = $localPart . '@' . $domains[array_rand($domains)];
            }

            $store    = $stores->random();
            $noteText = $notes[array_rand($notes)];

            $customer = Customer::create([
                'customer_id' => $patientId,
                'name'        => $name,
                'name_kana'   => $nameKana,
                'gender'      => $gender,
                'birth_date'  => $birthDate,
                'phone'       => $phone,
                'email'       => $email,
                'notes'       => $noteText,
                'store_id'    => $store->id,
            ]);

            // 診療履歴・予約（1〜8件）
            $historyCount = rand(1, 8);
            $staffList    = $staffByStore[$store->store_code];
            $staff        = $staffList[array_rand($staffList)];
            $baseDate     = now()->subDays(rand(30, 900));

            for ($j = 0; $j < $historyCount; $j++) {
                $t = $treatments[array_rand($treatments)];

                // 治療部位の決定
                $area = null;
                if ($t['area'] === 'tooth') {
                    $area = $toothNumbers[array_rand($toothNumbers)];
                } elseif ($t['area'] === 'area') {
                    $area = $areaOptions[array_rand($areaOptions)];
                } elseif ($t['area'] === 'wisdom') {
                    $area = $wisdomOptions[array_rand($wisdomOptions)];
                }

                // 最初の診療（j=0）は 30% の確率で未来（最大60日後）、残りは過去
                if ($j === 0 && rand(1, 10) <= 3) {
                    $treatedAt = now()->addDays(rand(1, 60));
                } else {
                    $treatedAt = $baseDate->copy()->subDays($j * rand(14, 60));
                }

                $reservationId = 'R' . str_pad($reservationSeq++, 4, '0', STR_PAD_LEFT);

                $isPast = $treatedAt->lte(now());
                if (!$isPast) {
                    // 未来 → reserved（10% はキャンセル）
                    $status = rand(1, 10) <= 1 ? 'cancelled' : 'reserved';
                } else {
                    // 過去 → completed（10% はキャンセル）
                    $status = rand(1, 10) <= 1 ? 'cancelled' : 'completed';
                }

                $reservation = Reservation::create([
                    'reservation_id' => $reservationId,
                    'customer_id'    => $customer->id,
                    'reserved_at'    => $treatedAt,
                    'staff'          => $staff,
                    'status'         => $status,
                ]);

                TreatmentHistory::create([
                    'customer_id'    => $customer->id,
                    'reservation_id' => $reservation->id,
                    'treatment_type' => $t['type'],
                    'treatment_area' => $area,
                    'treated_at'     => $treatedAt,
                    'staff'          => $staff,
                ]);
            }
        }
    }
}
