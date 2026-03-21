@extends('layouts.app')

@section('title', 'エラー詳細')

@section('content')

@php
$fieldLabels = [
    'patient_id'     => '患者ID',
    'customer_id'    => '患者ID',
    'name'           => '氏名',
    'gender'         => '性別',
    'birth_date'     => '生年月日',
    'phone'          => '電話番号',
    'email'          => 'メール',
    'notes'          => '備考',
    'clinic_code'    => '院コード',
    'store_code'     => '院コード',
    'reservation_id' => '予約ID',
    'reserved_at'    => '予約日時',
    'treatment_type' => '治療内容',
    'treatment_area' => '治療部位',
    'staff'          => '担当者',
    'status'         => 'ステータス',
];

function friendlyError(string $message): array {
    if (str_contains($message, 'clinic_code') || str_contains($message, 'store_code')) {
        return [
            'title' => '院コードが入力されていません（必須項目）',
            'hint'  => '「clinic_code」列に正しい院コードを入力してください。',
        ];
    }
    if (str_contains($message, 'patient_id') && str_contains($message, '空')) {
        return [
            'title' => '患者IDが入力されていません（必須項目）',
            'hint'  => '「patient_id」列に患者IDを入力してください。空欄のまま登録することはできません。',
        ];
    }
    if (str_contains($message, '存在しません') || str_contains($message, 'not found')) {
        return [
            'title' => '患者情報が見つかりません',
            'hint'  => 'この予約データの患者IDはまだ登録されていません。先に患者CSVをインポートしてから、再度予約CSVをインポートしてください。',
        ];
    }
    if (str_contains($message, 'name') || str_contains($message, '氏名')) {
        return [
            'title' => '氏名が入力されていません（必須項目）',
            'hint'  => '「name」列に患者の氏名を入力してください。',
        ];
    }
    if (str_contains($message, '不正なステータス')) {
        return [
            'title' => 'ステータスの値が正しくありません',
            'hint'  => '「status」列には reserved（予約）・completed（完了）・cancelled（キャンセル）のいずれかを入力してください。',
        ];
    }
    if (str_contains($message, '日付形式が不正') || str_contains($message, 'datetime')) {
        return [
            'title' => '日付・時刻の形式が正しくありません（必須項目）',
            'hint'  => '「datetime」列は「2026-03-20 10:00」の形式で入力してください。',
        ];
    }
    if (str_contains($message, 'NOT NULL') || str_contains($message, 'null value') || str_contains($message, 'cannot be null')) {
        return [
            'title' => '必須項目が入力されていません',
            'hint'  => '空欄になっている必須項目を確認し、正しく入力してから再度インポートしてください。',
        ];
    }
    return [
        'title' => 'データを登録できませんでした',
        'hint'  => '空欄になっている必須項目がないか確認してください。問題が解決しない場合はシステム担当者にお問い合わせください。',
    ];
}
@endphp

<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('import.history') }}"
       class="flex items-center justify-center w-9 h-9 rounded-xl bg-white border border-gray-200 hover:bg-gray-50 transition-colors">
        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
    </a>
    <div>
        <h1 class="text-2xl font-bold text-gray-900">エラー詳細</h1>
        <p class="text-sm text-gray-400">{{ $importJob->filename }} ・ {{ $importJob->created_at->format('Y/m/d H:i') }}</p>
    </div>
</div>

{{-- サマリー --}}
<div class="grid grid-cols-3 gap-3 md:gap-4 mb-6">
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm px-5 py-4">
        <p class="text-xs text-gray-400 mb-1">合計</p>
        <p class="text-2xl font-bold text-gray-900">{{ $importJob->total_rows }}<span class="text-sm font-normal text-gray-400 ml-1">件</span></p>
    </div>
    <div class="bg-green-50 rounded-2xl border border-green-100 shadow-sm px-5 py-4">
        <p class="text-xs text-green-600 mb-1">登録成功</p>
        <p class="text-2xl font-bold text-green-700">{{ $importJob->success_rows }}<span class="text-sm font-normal text-green-500 ml-1">件</span></p>
    </div>
    <div class="bg-red-50 rounded-2xl border border-red-100 shadow-sm px-5 py-4">
        <p class="text-xs text-red-500 mb-1">登録できなかった件数</p>
        <p class="text-2xl font-bold text-red-600">{{ $importJob->error_rows }}<span class="text-sm font-normal text-red-400 ml-1">件</span></p>
    </div>
</div>

{{-- 対処ガイド --}}
<div class="bg-amber-50 border border-amber-200 rounded-2xl px-5 py-4 mb-6 flex items-start gap-3">
    <svg class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    <div>
        <p class="text-sm font-semibold text-amber-800 mb-1">登録できなかったデータの対処方法</p>
        <p class="text-xs text-amber-700 leading-relaxed">
            下記のエラー一覧を確認し、CSVファイルの該当行を修正してから、再度インポートしてください。<br>
            修正後に再インポートすると、正常なデータは上書き更新されます。
        </p>
    </div>
</div>

{{-- エラー一覧 --}}
<div class="space-y-3">
    @forelse($errors as $error)
        @php $friendly = friendlyError($error->error_message); @endphp
        <div class="bg-white rounded-2xl border border-red-100 shadow-sm overflow-hidden">
            <div class="flex items-start gap-4 px-5 py-4">

                {{-- 行番号 --}}
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-red-50 text-xs font-bold text-red-500">
                        {{ $error->row_number }}行
                    </span>
                </div>

                <div class="flex-1 min-w-0">
                    {{-- エラー内容（わかりやすく） --}}
                    <div class="flex items-start gap-2 mb-3">
                        <svg class="w-4 h-4 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        <div>
                            <p class="text-sm font-semibold text-red-700">{{ $friendly['title'] }}</p>
                            <p class="text-xs text-gray-500 mt-0.5">{{ $friendly['hint'] }}</p>
                        </div>
                    </div>

                    {{-- 該当データ（項目名付き） --}}
                    @if($error->row_data)
                        <div class="bg-gray-50 rounded-xl p-3">
                            <p class="text-xs font-medium text-gray-400 mb-2">該当データ</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach($error->row_data as $key => $value)
                                    @if($value !== null && $value !== '')
                                        <div class="flex items-center gap-1.5 bg-white border border-gray-200 rounded-lg px-2.5 py-1">
                                            <span class="text-xs text-gray-400">{{ $fieldLabels[$key] ?? $key }}</span>
                                            <span class="text-xs font-medium text-gray-800">{{ $value }}</span>
                                        </div>
                                    @endif
                                @endforeach
                                {{-- 空欄項目も表示 --}}
                                @foreach($error->row_data as $key => $value)
                                    @if($value === null || $value === '')
                                        <div class="flex items-center gap-1.5 bg-red-50 border border-red-200 rounded-lg px-2.5 py-1">
                                            <span class="text-xs text-red-400">{{ $fieldLabels[$key] ?? $key }}</span>
                                            <span class="text-xs text-red-300 italic">未入力</span>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm px-6 py-12 text-center text-gray-400 text-sm">
            エラーデータがありません
        </div>
    @endforelse
</div>

@if($errors->hasPages())
    <div class="mt-4">{{ $errors->links() }}</div>
@endif

@endsection
