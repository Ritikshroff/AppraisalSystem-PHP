@props(['showVar'])

<div x-show="{{ $showVar }}" 
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm transition-opacity"
     x-cloak
     @keydown.escape.window="{{ $showVar }} = false">
    <div {{ $attributes->merge(['class' => 'bg-white border border-gray-200 w-full p-6 relative shadow-2xl space-y-6']) }}
         @click.away="{{ $showVar }} = false">
        <button @click="{{ $showVar }} = false" class="absolute top-4 right-4 text-gray-400 hover:text-black cursor-pointer">
            <i data-lucide="x" class="h-5 w-5"></i>
        </button>

        <div>
            <h3 class="text-lg font-bold text-black uppercase tracking-wider flex items-center gap-2 border-b border-gray-200 pb-3 font-mono">
                @if(isset($headerIcon))
                    {{ $headerIcon }}
                @endif
                @if(isset($title))
                    {{ $title }}
                @endif
            </h3>
        </div>

        <div>
            {{ $slot }}
        </div>
    </div>
</div>
