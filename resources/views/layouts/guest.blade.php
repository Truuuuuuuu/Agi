
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

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0  bg-[#F2F2F2]">
            
            <div class="w-full sm:max-w-md  ">
                <h1 class="text-4xl font-bold text-[#460C17]">  {{ $heading ?? ''}} </h1>
                <p class="text-md text-[#460C17] opacity-70">  {{ $subheading ?? ''}}</p>
            </div>
            <div class="w-full sm:max-w-md mt-6 px-6 py-8 bg-white shadow-md overflow-hidden rounded-3xl">
                
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
