@props([
    'name',
    'id'         => null,
    'label'      => null,
    'autosubmit' => false,
])

@php $fieldId = $id ?? ('ts_' . $name); @endphp

<div>
    @if($label)
        <x-input-label :value="$label" />
    @endif
    <select
        id="{{ $fieldId }}"
        name="{{ $name }}"
        data-tomselect
        @if($autosubmit) data-autosubmit="1" @endif
        {{ $attributes->merge(['class' => 'mt-1 block border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm']) }}>
        {{ $slot }}
    </select>
</div>

@once
@push('styles')
<style>
.ts-wrapper .ts-control {
    border-color: rgb(209 213 219);
    border-radius: 0.375rem;
    font-size: 0.875rem;
    min-height: unset;
    padding: 0.25rem 0.5rem;
}
.dark .ts-wrapper .ts-control {
    background-color: rgb(17 24 39);
    border-color: rgb(55 65 81);
    color: rgb(209 213 219);
}
.dark .ts-wrapper .ts-dropdown {
    background-color: rgb(31 41 55);
    border-color: rgb(55 65 81);
    color: rgb(209 213 219);
}
.dark .ts-wrapper .ts-dropdown .option:hover,
.dark .ts-wrapper .ts-dropdown .option.active {
    background-color: rgb(55 65 81);
}
.dark .ts-wrapper .ts-dropdown input {
    background-color: rgb(17 24 39);
    color: rgb(209 213 219);
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('select[data-tomselect]').forEach(function (el) {
        if (el.tomselect) return;
        var opts = { maxOptions: null };
        if (el.dataset.autosubmit) {
            opts.onChange = function () { el.closest('form').submit(); };
        }
        new TomSelect(el, opts);
    });
});
</script>
@endpush
@endonce
