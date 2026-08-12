<!DOCTYPE html>
<html lang="fr" class="antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Accès Refusé - Batistack</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    
    @vite('resources/css/app.css')
</head>
<body class="bg-batistack-50 text-batistack-700 min-h-screen flex items-center justify-center font-sans">
    
    <div class="max-w-2xl w-full px-6 py-12 bg-white rounded-2xl shadow-xl border border-batistack-100 text-center relative overflow-hidden">
        
        <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-batistack-700 to-accent-500"></div>
        <div class="absolute -top-24 -right-24 w-48 h-48 bg-batistack-50 rounded-full opacity-50"></div>
        <div class="absolute -bottom-24 -left-24 w-48 h-48 bg-accent-500/10 rounded-full opacity-50"></div>

        <div class="relative z-10">
            <div class="mx-auto w-20 h-20 bg-batistack-700 rounded-2xl flex items-center justify-center mb-8 shadow-lg transform -rotate-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>

            <h1 class="text-4xl font-extrabold tracking-tight text-batistack-800 mb-4">
                Accès Refusé
            </h1>
            
            <p class="text-lg text-batistack-500 mb-8 max-w-lg mx-auto leading-relaxed">
                Désolé, vous n'avez pas les autorisations nécessaires pour accéder à cette page.
            </p>

            <a href="/" class="inline-flex items-center space-x-2 bg-batistack-700 hover:bg-batistack-800 text-white px-6 py-3 rounded-lg font-semibold transition">
                <span>Retour à l'accueil</span>
            </a>
            
            <div class="mt-10 text-sm text-batistack-500">
                <p class="mt-2 text-xs font-mono text-batistack-400">Code HTTP : 403 - Forbidden</p>
            </div>
        </div>
    </div>

</body>
</html>
