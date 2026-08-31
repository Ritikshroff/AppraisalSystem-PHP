@props(['label', 'value', 'detail'])

<div class="bg-white border border-gray-200 p-6 flex flex-col justify-between h-full">
    <div>
        <dt class="truncate text-xs font-bold uppercase tracking-wider text-blue-500">{{ $label }}</dt>
        <dd class="mt-2 text-3xl font-extrabold tracking-tight text-black">{{ $value }}</dd>
    </div>
    <dd class="mt-3 text-xs text-gray-500 leading-normal">{{ $detail }}</dd>
</div>
