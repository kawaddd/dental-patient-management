<div class="grid grid-cols-3 gap-6">

    {{-- アップロードフォーム --}}
    <div class="col-span-2">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <form method="POST" action="{{ $action }}" enctype="multipart/form-data"
                  x-data="{ loading: false, filename: '', dragover: false }" @submit="loading = true">
                @csrf

                @if($errors->any())
                    <div class="mb-4 flex items-start gap-3 p-4 bg-red-50 border border-red-200 rounded-xl">
                        <svg class="w-5 h-5 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div>
                            @foreach($errors->all() as $error)
                                <p class="text-red-700 text-sm">{{ $error }}</p>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- ドロップゾーン --}}
                <label class="block cursor-pointer"
                       @dragover.prevent="dragover = true"
                       @dragleave.self="dragover = false"
                       @drop.prevent="
                           dragover = false;
                           let f = $event.dataTransfer.files[0];
                           if (f) {
                               let dt = new DataTransfer();
                               dt.items.add(f);
                               $el.querySelector('input[type=file]').files = dt.files;
                               filename = f.name;
                           }
                       ">
                    <div class="border-2 border-dashed rounded-2xl p-12 text-center transition-colors"
                         :class="dragover ? 'border-blue-400 bg-blue-50' : 'border-gray-200 hover:border-blue-300 hover:bg-blue-50'">
                        <div x-show="!filename">
                            <svg class="w-10 h-10 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                            </svg>
                            <p class="text-gray-500 text-sm font-medium mb-1">CSVファイルをドロップ</p>
                            <p class="text-gray-400 text-xs">または クリックしてファイルを選択</p>
                        </div>
                        <div x-show="filename" class="flex items-center justify-center gap-3">
                            <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div class="text-left">
                                <p class="text-sm font-medium text-gray-800" x-text="filename"></p>
                                <p class="text-xs text-gray-400">選択済み</p>
                            </div>
                        </div>
                        <input type="file" name="csv_file" accept=".csv,.txt" class="hidden"
                               @change="filename = $event.target.files[0]?.name ?? ''">
                    </div>
                </label>

                <button type="submit"
                        :disabled="loading || !filename"
                        class="mt-5 w-full flex items-center justify-center gap-2 min-h-[48px] px-6 rounded-xl text-sm font-semibold transition-all
                               bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-40 disabled:cursor-not-allowed">
                    <svg x-show="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span x-text="loading ? '処理中...' : 'インポート実行'"></span>
                </button>
            </form>
        </div>
    </div>

    {{-- CSV仕様 --}}
    <div class="col-span-1">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">CSV形式</h3>
            <div class="bg-gray-50 rounded-xl p-3 mb-4">
                <p class="text-xs font-mono text-gray-600 leading-relaxed break-all">{{ $format }}</p>
            </div>
            <h3 class="text-sm font-semibold text-gray-700 mb-2">例</h3>
            <div class="bg-gray-50 rounded-xl p-3 mb-4">
                <p class="text-xs font-mono text-gray-500 leading-relaxed break-all">{{ $example }}</p>
            </div>
            <div class="space-y-2 text-xs text-gray-500">
                <div class="flex items-start gap-2">
                    <svg class="w-3.5 h-3.5 text-blue-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>同一患者IDは上書き更新されます</span>
                </div>
                <div class="flex items-start gap-2">
                    <svg class="w-3.5 h-3.5 text-blue-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>エラー行はスキップして処理継続</span>
                </div>
                <div class="flex items-start gap-2">
                    <svg class="w-3.5 h-3.5 text-blue-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>最大5MB</span>
                </div>
            </div>
        </div>
    </div>

</div>
