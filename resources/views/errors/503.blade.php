<!DOCTYPE html>
<html lang="fr" class="antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Maintenance - Batistack</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    
    
    @vite('resources/css/app.css')
</head>
<body class="bg-batistack-50 text-batistack-700 min-h-screen flex items-center justify-center font-sans">
    
    <div class="max-w-2xl w-full px-6 py-12 bg-white rounded-2xl shadow-xl border border-batistack-100 text-center relative overflow-hidden">
        
        <!-- Decorative elements -->
        <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-batistack-700 to-accent-500"></div>
        <div class="absolute -top-24 -right-24 w-48 h-48 bg-batistack-50 rounded-full opacity-50"></div>
        <div class="absolute -bottom-24 -left-24 w-48 h-48 bg-accent-500/10 rounded-full opacity-50"></div>

        <div class="relative z-10">
            <!-- Logo / Icon -->
            <div class="mx-auto w-20 h-20 bg-batistack-700 rounded-2xl flex items-center justify-center mb-8 shadow-lg transform -rotate-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </div>

            <!-- Content -->
            <h1 class="text-4xl font-extrabold tracking-tight text-batistack-800 mb-4">
                Chantier en cours
            </h1>
            
            <p class="text-lg text-batistack-500 mb-8 max-w-lg mx-auto leading-relaxed">
                Notre plateforme <strong>Batistack</strong> fait actuellement l'objet d'une opération de maintenance planifiée afin d'améliorer vos outils de gestion.<br/><br/>
                L'accès sera rétabli dans quelques instants.
            </p>

            <!-- Status Indicator -->
            <div class="inline-flex items-center space-x-3 bg-batistack-50 px-6 py-3 rounded-full border border-batistack-100">
                <span class="relative flex h-3 w-3">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-accent-500 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-3 w-3 bg-accent-500"></span>
                </span>
                <span class="text-sm font-semibold text-batistack-600">Travaux d'infrastructure en cours...</span>
            </div>
            
            <div class="mt-10 text-sm text-batistack-500">
                <p>En cas d'urgence, veuillez contacter votre administrateur système.</p>
                <p class="mt-2 text-xs font-mono text-batistack-400">Code HTTP : 503 - Service Unavailable</p>
            </div>
        </div>
    </div>

</body>
</html>
