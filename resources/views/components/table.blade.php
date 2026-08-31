@props(['headers'])

<div class="overflow-x-auto border border-gray-200">
    <table class="min-w-full divide-y divide-gray-200 text-left text-xs">
        <thead class="bg-gray-50 border-b border-gray-200 text-[10px] font-bold text-gray-500 uppercase tracking-wider font-mono">
            <tr>
                @foreach($headers as $header)
                    <th scope="col" class="px-6 py-3 {{ str_contains(strtolower($header), 'action') || str_contains(strtolower($header), 'status') ? 'text-center' : '' }}">
                        {{ $header }}
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-100 text-black">
            {{ $slot }}
        </tbody>
    </table>
</div>
