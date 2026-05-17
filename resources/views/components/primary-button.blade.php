<button {{ $attributes->merge(['type' => 'submit', 'class' => 'flex justify-center items-center inline-flex items-center px-4 py-2  border border-transparent rounded-md font-semibold text-xs text-white  uppercase tracking-widest w-full hover:text-white bg-primary hover:bg-primary-hover ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
