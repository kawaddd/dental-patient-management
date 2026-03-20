<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportCsvRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'csv_file.required' => 'CSVファイルを選択してください',
            'csv_file.mimes'    => 'CSV形式のファイルを選択してください',
            'csv_file.max'      => 'ファイルサイズは5MB以下にしてください',
        ];
    }
}
