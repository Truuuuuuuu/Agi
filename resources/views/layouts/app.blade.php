<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/Agi_logo.png') }}">
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
    <!-- Scripts -->
    @vite(['resources/js/app.js'])
</head>

<body class="font-sans antialiased">
    <div class="min-h-screen bg-[#F2F2F2]">
        @include('layouts.navigation')

        <!-- Page Heading -->
        @isset($header)
            <header class="bg-[#F2F2F2] my-4 px-3 lg:px-8">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 rounded-3xl bg-primary">
                    {{ $header }}
                </div>
            </header>
        @endisset

        <!-- Page Content -->
        <main>
            {{ $slot }}
        </main>
    </div>

    {{-- Toast --}}
    <div x-data="toastManager()" x-init="init()" class="fixed top-2 left-1/2 -translate-x-1/2 space-y-2 z-50">
        <template x-for="toast in toasts" :key="toast.id">
            <div x-show="toast.show" x-transition class="flex gap-3 items-center px-4 py-3 rounded-xl shadow-lg text-sm text-white" :class="{
                'bg-green-600': toast.type === 'success',
                'bg-red-600': toast.type === 'error',
                'bg-blue-600': toast.type === 'info'
            }">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-check-icon lucide-circle-check"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
                <span x-text="toast.message"></span>
            </div>
        </template>
    </div>
</body>

</html>