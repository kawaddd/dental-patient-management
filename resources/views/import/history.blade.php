@extends('layouts.app')

@section('title', 'インポート履歴')

@section('content')

{{-- ページ全体を x-data で囲む --}}
<div x-data="deleteModal()">

{{-- 削除確認モーダル --}}
<div x-show="open"
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center">

    {{-- オーバーレイ --}}
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"
         @click="cancel()"></div>

    {{-- モーダル本体 --}}
    <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-sm mx-4 p-6"
         @click.stop
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">

        <div class="flex items-center justify-center w-12 h-12 rounded-full bg-red-50 mx-auto mb-4">
            <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
        </div>

        <h3 class="text-base font-bold text-gray-900 text-center mb-1">履歴を削除しますか？</h3>
        <p class="text-sm text-gray-500 text-center mb-1" x-text="filename"></p>
        <p class="text-xs text-center mb-6" :class="hasErrors ? 'text-red-500' : 'text-gray-400'">
            <span x-show="hasErrors">エラー詳細も合わせて削除されます。</span>この操作は取り消せません。
        </p>

        <div class="flex gap-3">
            <button @click="cancel()"
                    class="flex-1 px-4 py-2.5 bg-gray-100 text-gray-700 text-sm font-medium rounded-xl hover:bg-gray-200 transition-colors">
                キャンセル
            </button>
            <button @click="confirm()"
                    class="flex-1 px-4 py-2.5 bg-red-600 text-white text-sm font-medium rounded-xl hover:bg-red-700 transition-colors">
                削除する
            </button>
        </div>
    </div>
</div>

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">インポート履歴</h1>
        <p class="text-sm text-gray-500 mt-1">CSVインポートの実行履歴と結果</p>
    </div>
    <a href="{{ route('import.index') }}"
       class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-xl hover:bg-blue-700 transition-colors min-h-[44px]">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
        </svg>
        新規インポート
    </a>
</div>

<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    @forelse($jobs as $job)
        @php
            $statusMap = [
                'completed'  => ['label' => '完了',   'class' => 'bg-green-100 text-green-700'],
                'processing' => ['label' => '処理中', 'class' => 'bg-blue-100 text-blue-700'],
                'failed'     => ['label' => '失敗',   'class' => 'bg-red-100 text-red-700'],
            ];
            $s = $statusMap[$job->status] ?? ['label' => $job->status, 'class' => 'bg-gray-100 text-gray-600'];
        @endphp

        <div class="px-5 py-4 border-b border-gray-50 last:border-0 hover:bg-gray-50 transition-colors">
            <div class="flex items-start gap-3">

                {{-- タイプアイコン --}}
                <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 mt-0.5
                    {{ $job->type === 'customers' ? 'bg-blue-50' : 'bg-purple-50' }}">
                    <svg class="w-5 h-5 {{ $job->type === 'customers' ? 'text-blue-600' : 'text-purple-600' }}"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        @if($job->type === 'customers')
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        @else
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        @endif
                    </svg>
                </div>

                {{-- ファイル名・日時（左：flex-1）--}}
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-900 truncate">{{ $job->filename }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">
                        {{ $job->type === 'customers' ? '患者CSV' : '予約CSV' }}・{{ $job->created_at->format('Y/m/d H:i') }}
                    </p>
                </div>

                {{-- 右側：統計縦列 ＋ 削除ボタン --}}
                <div class="flex items-start gap-3 shrink-0">

                    {{-- 統計＋ステータス --}}
                    <div class="text-right space-y-1">
                        {{-- 1行目: 合計〇件 成功〇 [エラー〇] --}}
                        <p class="text-xs text-gray-500 whitespace-nowrap">
                            合計 <span class="font-semibold text-gray-700">{{ $job->total_rows }}</span>件
                            <span class="text-green-600 ml-1.5">成功 <span class="font-semibold">{{ $job->success_rows }}</span></span>
                            @if($job->error_rows > 0)
                                <span class="text-red-500 ml-1.5">エラー <span class="font-semibold">{{ $job->error_rows }}</span></span>
                            @endif
                        </p>
                        {{-- 2行目: ステータスバッジ [エラー詳細] --}}
                        <div class="flex items-center justify-end gap-1.5">
                            <span class="px-2 py-0.5 rounded-md text-xs font-medium whitespace-nowrap {{ $s['class'] }}">
                                {{ $s['label'] }}
                            </span>
                            @if($job->error_rows > 0)
                                <a href="{{ route('import.errors', $job) }}"
                                   class="flex items-center gap-0.5 text-xs text-red-500 hover:text-red-700 hover:underline font-medium whitespace-nowrap">
                                    エラー詳細
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </a>
                            @endif
                        </div>
                    </div>

                    {{-- 削除ボタン --}}
                    <form id="delete-form-{{ $job->id }}" method="POST" action="{{ route('import.destroy', $job) }}">
                        @csrf
                        @method('DELETE')
                    </form>
                    <button type="button"
                            @click="open = true; filename = '{{ addslashes($job->filename) }}'; formId = 'delete-form-{{ $job->id }}'; hasErrors = {{ $job->error_rows > 0 ? 'true' : 'false' }}"
                            class="flex items-center justify-center w-7 h-7 rounded-lg text-gray-300 hover:text-red-500 hover:bg-red-50 transition-colors shrink-0 mt-0.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>

                </div>
            </div>
        </div>
    @empty
        <div class="px-6 py-16 text-center">
            <svg class="w-10 h-10 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
            </svg>
            <p class="text-gray-400 text-sm">インポート履歴がありません</p>
            <a href="{{ route('import.index') }}" class="inline-block mt-3 text-sm text-blue-600 hover:underline">CSVをインポートする</a>
        </div>
    @endforelse
</div>

@if($jobs->hasPages())
    <div class="mt-4">{{ $jobs->links() }}</div>
@endif

</div>

<script>
function deleteModal() {
    return {
        open:      false,
        filename:  '',
        formId:    '',
        hasErrors: false,
        cancel() { this.open = false; },
        confirm() { document.getElementById(this.formId).submit(); },
    };
}
</script>

@endsection
