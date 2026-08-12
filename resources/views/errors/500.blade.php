<!DOCTYPE html>
<html lang="fr" class="antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Erreur Interne - Batistack</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'], },
                    colors: {
                        batistack: { 50: '#f0f4f8', 100: '#d9e2ec', 500: '#334e68', 600: '#243b53', 700: '#102a43', 800: '#0a192f', 900: '#060f1c', },
                        accent: { 500: '#f59e0b', }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-batistack-50 text-batistack-700 min-h-screen flex items-center justify-center font-sans">
    
    <div class="max-w-2xl w-full px-6 py-12 bg-white rounded-2xl shadow-xl border border-batistack-100 text-center relative overflow-hidden">
        
        <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-batistack-700 to-accent-500"></div>
        <div class="absolute -top-24 -right-24 w-48 h-48 bg-batistack-50 rounded-full opacity-50"></div>
        <div class="absolute -bottom-24 -left-24 w-48 h-48 bg-accent-500/10 rounded-full opacity-50"></div>

        <div class="relative z-10">
            <div class="mx-auto w-20 h-20 bg-batistack-700 rounded-2xl flex items-center justify-center mb-8 shadow-lg transform -rotate-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            </div>

            <h1 class="text-4xl font-extrabold tracking-tight text-batistack-800 mb-4">
                Erreur Interne
            </h1>
            
            <p class="text-lg text-batistack-500 mb-8 max-w-lg mx-auto leading-relaxed">
                Un problème technique inattendu est survenu sur nos installations. Nos équipes ont été prévenues et s'en occupent.
            </p>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="/" class="inline-flex items-center space-x-2 bg-batistack-700 hover:bg-batistack-800 text-white px-6 py-3 rounded-lg font-semibold transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M9.707 14.707a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 1.414L7.414 9H15a1 1 0 110 2H7.414l2.293 2.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                    </svg>
                    <span>Retour à l'accueil</span>
                </a>
                
                @if(app()->bound('sentry') && app('sentry')->getLastEventId())
                    <button onclick="Sentry.showReportDialog({ eventId: '{{ app('sentry')->getLastEventId() }}', lang: 'fr' })" class="inline-flex items-center space-x-2 bg-white border border-batistack-200 hover:bg-batistack-50 text-batistack-700 px-6 py-3 rounded-lg font-semibold transition shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-accent-500" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-2 0c0 .993-.241 1.929-.668 2.754l-1.524-1.525a3.997 3.997 0 00.078-2.183l1.562-1.04L14.04 6.64l-1.503 1.054A3.997 3.997 0 0010.5 7.15V5.3l-2.022-.505-.506 2.023a4.015 4.015 0 00-2.182.078l-1.04-1.562-1.366 1.408 1.054 1.503a3.997 3.997 0 00-.544 2.037l-1.85.463.505 2.022 1.85-.463a4.015 4.015 0 00.078 2.182l-1.562 1.04 1.408 1.366 1.503-1.054a3.997 3.997 0 002.037.544l.463 1.85 2.022-.505-.463-1.85a4.015 4.015 0 002.182-.078l1.04 1.562 1.366-1.408-1.054-1.503a3.997 3.997 0 00.544-2.037l1.85-.463zM9 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                        </svg>
                        <span>Signaler le problème</span>
                    </button>
                    <!-- Sentry SDK for Report Dialog -->
                    <script src="https://browser.sentry-cdn.com/7.100.0/bundle.min.js"></script>
                    <script>
                        Sentry.init({ dsn: '{{ config('sentry.dsn') }}' });
                    </script>
                @else
                    <a href="mailto:support@batistack.fr?subject=Erreur%20500" class="inline-flex items-center space-x-2 bg-white border border-batistack-200 hover:bg-batistack-50 text-batistack-700 px-6 py-3 rounded-lg font-semibold transition shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-accent-500" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-2 0c0 .993-.241 1.929-.668 2.754l-1.524-1.525a3.997 3.997 0 00.078-2.183l1.562-1.04L14.04 6.64l-1.503 1.054A3.997 3.997 0 0010.5 7.15V5.3l-2.022-.505-.506 2.023a4.015 4.015 0 00-2.182.078l-1.04-1.562-1.366 1.408 1.054 1.503a3.997 3.997 0 00-.544 2.037l-1.85.463.505 2.022 1.85-.463a4.015 4.015 0 00.078 2.182l-1.562 1.04 1.408 1.366 1.503-1.054a3.997 3.997 0 002.037.544l.463 1.85 2.022-.505-.463-1.85a4.015 4.015 0 002.182-.078l1.04 1.562 1.366-1.408-1.054-1.503a3.997 3.997 0 00.544-2.037l1.85-.463zM9 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                        </svg>
                        <span>Signaler le problème</span>
                    </a>
                @endif
            </div>
            
            <div class="mt-10 text-sm text-batistack-500">
                <p class="mt-2 text-xs font-mono text-batistack-400">Code HTTP : 500 - Server Error</p>
            </div>
        </div>
    </div>

</body>
</html>
