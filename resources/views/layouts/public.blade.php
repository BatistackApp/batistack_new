<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="fi">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Batistack - Espace Public</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
        @filamentStyles

        <style>
            :root {
                @foreach (\Filament\Support\Facades\FilamentColor::getColors() as $name => $palette)
                    @foreach ($palette as $shade => $color)
                        --{{ $name }}-{{ $shade }}: {{ $color }};
                    @endforeach
                @endforeach
                --primary-500: var(--orange-500); /* Fallback */
            }
        </style>
    </head>
    <body class="font-sans antialiased text-gray-900 fi-body bg-gray-50">
        {{ $slot }}

        @livewireScripts
        @filamentScripts
        @fluxScripts
    </body>
</html>
