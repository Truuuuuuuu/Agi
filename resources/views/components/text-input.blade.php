@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-[#460C17]  focus:border-[#460C17] focus:ring-[#460C17]  rounded-md shadow-sm']) }}>
