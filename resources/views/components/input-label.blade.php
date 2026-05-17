@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-medium text-sm text-[#460C17] ']) }}>
    {{ $value ?? $slot }}
</label>
