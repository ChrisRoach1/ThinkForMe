<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="Generate your next million dollar idea with AI. Using GPT-4 and DALL-E-3, quickly generate project ideas and custom icons to go with it.">
        <link rel="icon" type="image/x-icon" href="">
        <title>
            @isset($title)
                {{ $title }} |
            @endisset
            {{ config('app.name') }}
        </title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @filamentScripts
        @filamentStyles

        <style>
            [x-cloak] {
                display: none !important;
            }
        </style>
    </head>
    <body class="font-sans antialiased bg-gray-50">
        <div class="min-h-screen">
            <livewire:layout.navigation />

            <main class="grow container mx-auto p-4">
                {{ $slot }}
                @livewire('notifications')
            </main>

            @include("components.footer")
        </div>
    </body>
</html>
