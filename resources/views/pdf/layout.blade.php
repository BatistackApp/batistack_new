<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ $title ?? 'document' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @font-face {
            font-family: 'Noto Sans';
            src: url('https://fonts.googleapis.com/css2?family=Noto+Sans:wght@400;700&display=swap');
        }
        body {
            font-family: 'Noto Sans', sans-serif;
            font-size: 12px;
            color: #1a202c;
        }
        /* Style spécifique pour les sauts de page mentionné dans le Canvas */
        .page-break {
            page-break-after: always;
        }
        .text-blue-batistack {
            color: #002157;
        }
        .bg-blue-batistack {
            background-color: #002157;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background-color: #002157;
            color: white;
            padding: 8px;
            text-align: left;
            text-transform: uppercase;
            font-size: 10px;
        }
        td {
            padding: 8px;
            border-bottom: 1px solid #e2e8f0;
        }
        .bg-gray-header {
            background-color: #f8fafc;
        }

        .envelope-window {
            position: absolute;
            top: 55mm;
            right: 20mm;
            width: 90mm;
            height: 35mm;
        }
    </style>
    @yield('styles')
</head>
<body class="bg-white text-slate-900 font-sans p-8 antialiased">
    <header class="flex flex-row border-b-2 border-b-gray-600 mb-5">
        <div class="logo-entreprise">
            <img src="{{ $company->getMedia('core')->first() }}" alt="">
        </div>
        <div class="flex flex-col">
            <span class="font-bold text-2xl">{{ $company->legal_name }}</span>
            <span>{{ $company->address }}</span>
            <span>{{ $company->zip_code }} {{ $company->city }}</span>
            <span>Téléphone: {{ $company->phone }}</span>
            <span>Email: {{ $company->email }}</span>
            <span>Siret: {{ $company->siret }}</span>
        </div>
    </header>
@yield('content')
</body>
</html>
