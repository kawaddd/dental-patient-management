<?php

namespace Database\Seeders;

use App\Models\ImportError;
use App\Models\ImportJob;
use Illuminate\Database\Seeder;

class ImportHistorySeeder extends Seeder
{
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
            'filename'     => 'reservations_2026-03-19.csv',
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

        // 処理中ジョブ
        ImportJob::create([
            'type'         => 'customers',
            'filename'     => 'patients_2026-03-20_v2.csv',
            'status'       => 'processing',
            'total_rows'   => 0,
            'success_rows' => 0,
            'error_rows'   => 0,
            'created_at'   => now()->subMinutes(2),
        ]);
    }
}
