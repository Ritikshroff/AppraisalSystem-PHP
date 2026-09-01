@props([
    'name' => null,
    'id' => null,
    'placeholder' => 'Select an option...',
    'options' => [],
    'value' => null,
    'disabled' => false,
    'model' => null,
    'required' => false,
    'class' => '',
])

<div 
    x-data="{
        open: false,
        selected: @js($value),
        selectedLabel: '',
        optionsList: @js($options),
        init() {
            if (this.selected !== null && this.selected !== '') {
                const found = this.optionsList.find(o => String(o.value) === String(this.selected));
                if (found) this.selectedLabel = found.label;
            }
            if (!this.selectedLabel) this.selectedLabel = '{{ $placeholder }}';

            this.$watch('selected', (newVal) => {
                const found = this.optionsList.find(o => String(o.value) === String(newVal));
                this.selectedLabel = found ? found.label : '{{ $placeholder }}';
            });
        },
        selectOption(opt) {
            if ({{ $disabled ? 'true' : 'false' }}) return;
            this.selected = opt.value;
            this.selectedLabel = opt.label;
            this.open = false;
            this.$dispatch('change', opt.value);
        }
    }"
    @if($model)
        x-modelable="selected"
        x-model="{{ $model }}"
    @endif
    class="relative w-full inline-block text-left {{ $class }}"
    @click.away="open = false"
>
    <!-- Hidden native input for form submissions -->
    @if($name)
        <input type="hidden" :name="{{ $name ? '\''.$name.'\'' : 'null' }}" :value="selected" @if($required) required @endif>
    @endif

    <!-- Trigger Button -->
    <button 
        type="button"
        @click="open = !open"
        :disabled="{{ $disabled ? 'true' : 'false' }}"
        class="w-full border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-800 flex justify-between items-center transition-all focus:outline-none focus:border-blue-500 hover:border-gray-400 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed shadow-2xs"
        :class="{ 'border-blue-500 ring-1 ring-blue-500': open }"
    >
        <span class="truncate" x-text="selectedLabel || '{{ $placeholder }}'" :class="{ 'text-gray-400': !selected && selected !== 0 }"></span>
        <i data-lucide="chevron-down" class="h-3.5 w-3.5 text-gray-500 transition-transform duration-200" :class="{ 'rotate-180 text-blue-500': open }"></i>
    </button>

    <!-- Dropdown Menu Options -->
    <div 
        x-show="open" 
        x-cloak
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute left-0 right-0 z-40 mt-1 max-h-56 overflow-y-auto bg-white border border-gray-200 shadow-xl py-1 text-xs font-medium focus:outline-none"
    >
        <template x-for="opt in optionsList" :key="opt.value">
            <button 
                type="button"
                @click="selectOption(opt)"
                class="w-full text-left px-3 py-2 flex items-center justify-between hover:bg-blue-50 hover:text-blue-600 transition-colors cursor-pointer"
                :class="{ 'bg-blue-50/70 text-blue-600 font-bold': String(selected) === String(opt.value) }"
            >
                <span x-text="opt.label"></span>
                <i x-show="String(selected) === String(opt.value)" data-lucide="check" class="h-3.5 w-3.5 text-blue-600"></i>
            </button>
        </template>
    </div>
</div>
