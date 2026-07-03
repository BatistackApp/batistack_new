<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Batistack - Espace Public</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
        @filamentStyles
    </head>
    <body class="font-sans antialiased text-gray-900">
        {{ $slot }}

        @livewireScripts
        @filamentScripts
    </body>
</html>
