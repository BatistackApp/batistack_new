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
            margin: 20mm;
        }
        /* Style spécifique pour les sauts de page mentionné dans le Canvas */
        .page-break {
            page-break-after: always;
        }
        .avoid-break {
            page-break-inside: avoid;
        }
        .section-page-break {
            page-break-before: always;
        }
        .section-page-break:first-of-type {
            page-break-before: auto;
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

        .container {
            max-width: 210mm;
            height: 297mm;
            margin: 0 auto;
            padding: 20mm;
            background: white;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 3px solid #1e40af;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .company-info {
            display: flex;
            align-items: flex-start;
        }

        .company-info h1 {
            font-size: 24px;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 5px;
        }

        .company-details {
            font-size: 10px;
            color: #666;
            line-height: 1.4;
        }

        /* En-tête */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 3px solid #1e40af;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .company-info h1 {
            font-size: 24px;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 5px;
        }

        .company-details {
            font-size: 10px;
            color: #666;
            line-height: 1.4;
        }

        .quote-info {
            text-align: right;
        }

        .quote-info .label {
            font-weight: bold;
            color: #1e40af;
            font-size: 11px;
        }

        .quote-info .value {
            font-size: 13px;
            font-weight: bold;
        }

        /* Informations client */
        .client-section {
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
        }

        .client-info, .project-info {
            width: 45%;
        }

        .section-title {
            font-size: 11px;
            font-weight: bold;
            color: #1e40af;
            border-bottom: 1px solid #1e40af;
            padding-bottom: 5px;
            margin-bottom: 8px;
        }

        .section-content {
            font-size: 10px;
            line-height: 1.5;
        }

        /* Table des articles */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            border-radius: 5em;
            margin: 20px 0;
        }

        .items-table thead {
            background-color: #1e40af;
            color: white;
        }

        .items-table th {
            padding: 8px;
            text-align: left;
            font-weight: bold;
            font-size: 11px;
            border: 1px solid #1e40af;
        }

        .items-table td {
            padding: 8px;
            border: 1px solid #ddd;
            font-size: 10px;
        }

        .items-table tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .qty {
            width: 10%;
        }

        .price {
            width: 15%;
        }

        .total {
            width: 15%;
        }

        /* Totaux */
        .totals-section {
            float: right;
            width: 40%;
            margin-top: 20px;
        }

        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 8px;
            border: 1px solid #ddd;
            font-size: 11px;
        }

        .totals-table .label {
            text-align: right;
            font-weight: bold;
            width: 60%;
            background-color: #f0f0f0;
        }

        .totals-table .value {
            text-align: right;
            width: 40%;
            background-color: #f0f0f0;
        }

        .totals-table .total-row .label,
        .totals-table .total-row .value {
            background-color: #1e40af;
            color: white;
            font-weight: bold;
            font-size: 12px;
        }

        /* Conditions */
        .conditions {
            clear: both;
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
        }

        .conditions-title {
            font-size: 11px;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 8px;
        }

        .conditions-content {
            font-size: 10px;
            color: #666;
            line-height: 1.5;
        }

        /* Pied de page */
        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            font-size: 9px;
            color: #999;
            text-align: center;
            position: fixed;
            bottom: 10mm;
            left: 20mm;
            right: 20mm;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
    <style>
        .gantt-container { overflow-x: auto; }
        .bar-milestone .bar { fill: #f97316; }
        .bar-phase .bar { fill: #3b82f6; }
        .bar-task .bar { fill: #94a3b8; }
        .bar-task-completed .bar { fill: #22c55e; }
    </style>
    @yield('styles')
    <link rel="stylesheet" href="https://unpkg.com/frappe-gantt@1.2.2/dist/frappe-gantt.css">
    <script src="https://unpkg.com/frappe-gantt@1.2.2/dist/frappe-gantt.umd.js"></script>
</head>
<body class="bg-white text-slate-900 font-sans p-8 antialiased">
<header class="header">
    <div class="company-info">
        <div class="logo-entreprise" style="vertical-align: top;">
            <img src="{{ \Illuminate\Support\Facades\Storage::url('core/company_logo.png') }}" alt="logo" style="max-width: 150px; max-height: 70px;">
        </div>
        <div class="company-details" style="display: inline-block; margin-left: 20px;">
            <span style="font-weight: bold; font-size: 1.2rem; color: #1e40af;">{{ $company?->legal_name ?? 'Batistack' }}</span><br>
            <span>{{ $company?->address ?? '' }}</span><br>
            <span>{{ $company?->zip_code ?? '' }} {{ $company?->city ?? '' }}</span><br>
            <span>Téléphone: {{ $company?->phone ?? '' }}</span><br>
            <span>Email: {{ $company?->email ?? '' }}</span>
        </div>
    </div>
    @yield("header_right")
</header>
<main style="padding-bottom: 40mm;">
    @yield('content')
</main>
<div class="footer">
    {{ $company?->legal_name ?? 'Batistack' }} @if($company?->capital)au capital de {{ number_format($company->capital, 2, ',', ' ') }}€@endif - SIRET: {{ $company?->siret ?? '' }} <br>
    {{ $company?->address ?? '' }}, {{ $company?->zip_code ?? '' }} {{ $company?->city ?? '' }} - TVA Intracommunautaire: {{ $company?->vat_number ?? 'Non applicable' }}
</div>
</body>
</html>
