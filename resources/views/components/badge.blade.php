@props(['status'])

@php
$map = [
    'reserved'  => ['class' => 'bg-blue-100 text-blue-700',  'label' => '予約済み'],
    'completed' => ['class' => 'bg-green-100 text-green-700','label' => '処置完了'],
    'cancelled' => ['class' => 'bg-gray-100 text-gray-500',  'label' => 'キャンセル'],
];
$item  = $map[$status] ?? ['class' => 'bg-gray-100 text-gray-500', 'label' => $status];
@endphp

<span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-medium {{ $item['class'] }}">
    {{ $item['label'] }}
</span>
