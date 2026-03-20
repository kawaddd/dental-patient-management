@extends('layouts.app')

@section('title', 'CSVインポート')

@section('content')

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">CSVインポート</h1>
    <p class="text-sm text-gray-500 mt-1">患者情報・予約情報をCSVファイルから一括取り込みします</p>
</div>

<div x-data="{ tab: 'customers' }">

    {{-- タブ --}}
    <div class="flex gap-1 mb-6 bg-gray-100 p-1 rounded-xl w-fit">
        <button @click="tab = 'customers'"
                :class="tab === 'customers' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                class="px-5 py-2 rounded-lg text-sm font-medium transition-all min-h-[36px]">
            患者CSV
        </button>
        <button @click="tab = 'reservations'"
                :class="tab === 'reservations' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                class="px-5 py-2 rounded-lg text-sm font-medium transition-all min-h-[36px]">
            予約CSV
        </button>
    </div>

    {{-- 患者CSV --}}
    <div x-show="tab === 'customers'" x-cloak>
        {{-- 注意書き --}}
        <div class="bg-blue-50 border border-blue-200 rounded-2xl px-5 py-4 mb-5 flex items-start gap-3">
            <svg class="w-5 h-5 text-blue-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div class="text-sm text-blue-800 space-y-1">
                <p class="font-semibold">患者CSVをインポートします</p>
                <p class="text-blue-700">このタブでは<strong>患者情報</strong>（氏名・生年月日・連絡先など）を登録・更新します。<br>
                予約・診療データを取り込む場合は「予約CSV」タブに切り替えてください。</p>
                <p class="text-blue-600 text-xs mt-1">必須項目：患者ID（patient_id）・氏名（name）・院コード（clinic_code）</p>
            </div>
        </div>
        @include('import._form', [
            'action' => route('import.customers'),
            'format' => 'patient_id, name, name_kana, gender, birth_date, phone, email, notes, clinic_code',
            'example' => 'P001,山田太郎,ヤマダタロウ,male,1990-05-15,090-1234-5678,yamada@example.com,金属アレルギーあり,C001',
        ])
    </div>

    {{-- 予約CSV --}}
    <div x-show="tab === 'reservations'" x-cloak>
        {{-- 注意書き --}}
        <div class="bg-purple-50 border border-purple-200 rounded-2xl px-5 py-4 mb-5 flex items-start gap-3">
            <svg class="w-5 h-5 text-purple-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div class="text-sm text-purple-800 space-y-1">
                <p class="font-semibold">予約CSVをインポートします</p>
                <p class="text-purple-700">このタブでは<strong>予約・診療履歴</strong>を登録・更新します。<br>
                患者IDが先に登録されていない場合はエラーになります。患者CSVを先にインポートしてください。</p>
                <p class="text-purple-600 text-xs mt-1">
                    必須項目：予約ID（reservation_id）・患者ID（patient_id）・日時（datetime）・ステータス（status）<br>
                    status の値：<strong>reserved</strong>（予約）・<strong>completed</strong>（完了）・<strong>cancelled</strong>（キャンセル）
                </p>
            </div>
        </div>
        @include('import._form', [
            'action' => route('import.reservations'),
            'format' => 'reservation_id, patient_id, datetime, doctor, status, treatment_type, treatment_area',
            'example' => 'R001,P001,2026-03-20 10:00,佐藤先生,completed,根管治療（神経治療）,右上7番',
        ])
    </div>

</div>

@endsection
