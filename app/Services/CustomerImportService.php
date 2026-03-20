<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\ImportError;
use App\Models\ImportJob;
use App\Models\Store;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\LazyCollection;

class CustomerImportService
{
    public function handle(UploadedFile $file): ImportJob
    {
        $job = ImportJob::create([
            'type'     => 'customers',
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
                $storeCode = $row['clinic_code'] ?? $row['store_code'] ?? null;
                if (!$storeCode) {
                    throw new \Exception('clinic_code が空です');
                }

                $patientId = $row['patient_id'] ?? $row['customer_id'] ?? null;
                if (!$patientId) {
                    throw new \Exception('patient_id が空です');
                }

                if (empty(trim($row['name'] ?? ''))) {
                    throw new \Exception('氏名（name）が空です');
                }

                $store = Store::firstOrCreate(
                    ['store_code' => $storeCode],
                    ['name'       => $storeCode]
                );

                Customer::updateOrCreate(
                    ['customer_id' => $patientId],
                    [
                        'name'      => $row['name'],
                        'name_kana' => ($row['name_kana'] ?? '') ?: null,
                        'gender'    => ($row['gender'] ?? '') ?: null,
                        'birth_date'=> ($row['birth_date'] ?? '') ?: null,
                        'phone'     => ($row['phone'] ?? '') ?: null,
                        'email'     => ($row['email'] ?? '') ?: null,
                        'notes'     => ($row['notes'] ?? '') ?: null,
                        'store_id'  => $store->id,
                    ]
                );

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
