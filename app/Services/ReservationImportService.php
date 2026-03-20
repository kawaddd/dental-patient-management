<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\ImportError;
use App\Models\ImportJob;
use App\Models\Reservation;
use App\Models\TreatmentHistory;
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
                $patientId = $row['patient_id'] ?? $row['customer_id'] ?? null;
                $customer  = Customer::where('customer_id', $patientId)->first();

                if (!$customer) {
                    throw new \Exception("患者ID {$patientId} が存在しません");
                }

                $datetime = $row['datetime'] ?? '';
                if (empty($datetime) || !strtotime($datetime)) {
                    throw new \Exception("日付形式が不正です: {$datetime}");
                }

                $status = strtolower($row['status'] ?? 'reserved');
                if (!in_array($status, ['reserved', 'completed', 'cancelled'])) {
                    throw new \Exception("不正なステータス: {$status}");
                }

                $reservation = Reservation::updateOrCreate(
                    ['reservation_id' => $row['reservation_id']],
                    [
                        'customer_id' => $customer->id,
                        'reserved_at' => $row['datetime'],
                        'staff'       => ($row['doctor'] ?? '') ?: null,
                        'status'      => $status,
                    ]
                );

                $treatmentType = $row['treatment_type'] ?? '';
                if (!empty($treatmentType)) {
                    TreatmentHistory::updateOrCreate(
                        ['reservation_id' => $reservation->id],
                        [
                            'customer_id'    => $customer->id,
                            'treated_at'     => $row['datetime'],
                            'treatment_type' => $treatmentType,
                            'treatment_area' => ($row['treatment_area'] ?? '') ?: null,
                            'staff'          => ($row['doctor'] ?? '') ?: null,
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
