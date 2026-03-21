@extends('layouts.app')

@section('title', 'ユーザー管理')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">ユーザー管理</h1>
        <p class="text-sm text-gray-500 mt-1">システムにアクセスできるユーザーの管理</p>
    </div>
    <a href="{{ route('users.create') }}"
       class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-xl hover:bg-blue-700 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        ユーザーを追加
    </a>
</div>

<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-x-auto">
    <table class="w-full text-sm" style="min-width: 560px;">
        <thead>
            <tr class="border-b border-gray-100 bg-gray-50">
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide">名前</th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide">メールアドレス</th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide w-24">パスワード</th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide w-24">登録日</th>
                <th class="px-5 py-3 text-right text-xs font-semibold text-gray-400 uppercase tracking-wide w-20">操作</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($users as $user)
            <tr class="hover:bg-gray-50 transition-colors {{ $user->id === auth()->id() ? 'bg-blue-50/30' : '' }}">

                {{-- 名前 --}}
                <td class="px-5 py-3">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-medium text-gray-800">{{ $user->name }}</span>
                            @if($user->is_admin)
                                <span class="text-xs font-medium text-amber-700 bg-amber-50 border border-amber-200 px-1.5 py-0.5 rounded-md whitespace-nowrap shrink-0">管理者</span>
                            @endif
                        </div>
                        @if($user->id === auth()->id())
                            <div class="text-xs text-blue-500 mt-0.5">自分のアカウント</div>
                        @endif
                    </div>
                </td>

                {{-- メール --}}
                <td class="px-5 py-3 text-gray-600">{{ $user->email }}</td>

                {{-- パスワード（マスク） --}}
                <td class="px-5 py-3">
                    <span class="text-gray-400 tracking-widest text-base">••••••••</span>
                </td>

                {{-- 登録日 --}}
                <td class="px-5 py-3 text-gray-400 text-xs whitespace-nowrap">{{ $user->created_at->format('Y/m/d') }}</td>

                {{-- 操作 --}}
                <td class="px-5 py-3 text-right">
                    <div class="flex items-center justify-end gap-2">
                        @if($user->id === auth()->id())
                        <a href="{{ route('users.edit', $user) }}"
                           class="px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors whitespace-nowrap">
                            編集
                        </a>
                        @endif
                        @if(auth()->user()->is_admin && $user->id !== auth()->id())
                            <form method="POST" action="{{ route('users.destroy', $user) }}"
                                  onsubmit="return confirm('「{{ $user->name }}」を削除してよろしいですか？\nこの操作は取り消せません。')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="px-3 py-1.5 text-xs font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition-colors whitespace-nowrap">
                                    削除
                                </button>
                            </form>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="px-5 py-12 text-center text-gray-400 text-sm">
                    ユーザーが登録されていません
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($users->hasPages())
    <div class="mt-4">{{ $users->links() }}</div>
@endif

@endsection
