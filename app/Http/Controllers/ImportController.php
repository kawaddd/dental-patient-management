<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportCsvRequest;
use App\Models\ImportJob;
use App\Services\CustomerImportService;
use App\Services\ReservationImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ImportController extends Controller
{
    public function __construct(
        private CustomerImportService    $customerImportService,
        private ReservationImportService $reservationImportService,
    ) {}

    public function index(): View
    {
        return view('import.index');
    }

    public function importCustomers(ImportCsvRequest $request): RedirectResponse
    {
        $job = $this->customerImportService->handle($request->file('csv_file'));

        $message = "インポート完了: 成功 {$job->success_rows}件";
        if ($job->error_rows > 0) {
            $message .= " / エラー {$job->error_rows}件";
        }

        return redirect()->route('import.history')->with('success', $message);
    }

    public function importReservations(ImportCsvRequest $request): RedirectResponse
    {
        $job = $this->reservationImportService->handle($request->file('csv_file'));

        $message = "インポート完了: 成功 {$job->success_rows}件";
        if ($job->error_rows > 0) {
            $message .= " / エラー {$job->error_rows}件";
        }

        return redirect()->route('import.history')->with('success', $message);
    }

    public function history(): View
    {
        $jobs = ImportJob::latest()->paginate(20);
        return view('import.history', compact('jobs'));
    }

    public function errors(ImportJob $importJob): View
    {
        $errors = $importJob->errors()->paginate(50);
        return view('import.errors', compact('importJob', 'errors'));
    }

    public function destroy(ImportJob $importJob): RedirectResponse
    {
        $importJob->delete();
        return redirect()->route('import.history')->with('success', 'インポート履歴を削除しました');
    }
}
