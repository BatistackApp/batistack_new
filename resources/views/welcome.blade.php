<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Batistack ERP - Portail de Connexion</title>
    
    <!-- PWA -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#f97316">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    
    <script>
        if ("serviceWorker" in navigator) {
            window.addEventListener("load", () => {
                navigator.serviceWorker.register("/sw.js")
                    .then(reg => console.log("Service Worker registered!"))
                    .catch(err => console.log("Service Worker registration failed", err));
            });
        }
    </script>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    
    <!-- Tailwind CSS (via CDN for standalone view, assuming Tailwind might not be fully compiled for front) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: { primary: '#f97316' } // orange-500
                }
            }
        }
    </script>
</head>
<body class="bg-slate-900 text-white min-h-screen flex flex-col items-center justify-center relative overflow-x-hidden">

    <!-- Background Decoration -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-1/2 -left-1/2 w-full h-full bg-gradient-to-br from-orange-500/20 to-transparent rounded-full blur-3xl opacity-50"></div>
        <div class="absolute -bottom-1/2 -right-1/2 w-full h-full bg-gradient-to-tl from-blue-500/20 to-transparent rounded-full blur-3xl opacity-50"></div>
        <!-- Grid pattern -->
        <div class="absolute inset-0 bg-[linear-gradient(rgba(255,255,255,0.02)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.02)_1px,transparent_1px)] bg-[size:4rem_4rem] [mask-image:radial-gradient(ellipse_60%_60%_at_50%_50%,#000_70%,transparent_100%)]"></div>
    </div>

    <div class="relative z-10 w-full max-w-6xl px-6 py-12 flex flex-col items-center">
        <!-- Logo Header -->
        <div class="mb-12 text-center">
            <div class="flex items-center justify-center space-x-3 mb-4">
                <svg class="w-12 h-12 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
                <h1 class="text-4xl md:text-5xl font-extrabold tracking-tight">BATISTACK</h1>
            </div>
            <p class="text-slate-400 text-lg md:text-xl max-w-2xl mx-auto">
                Sélectionnez votre espace de travail pour vous connecter à l'ERP de gestion de chantiers.
            </p>
        </div>

        <!-- Portals Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 w-full max-w-5xl">
            
            <!-- Core / Admin -->
            <a href="/core" class="group relative flex flex-col bg-slate-800/50 backdrop-blur-md border border-slate-700/50 hover:border-orange-500/50 rounded-2xl p-6 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-orange-500/10 hover:bg-slate-800">
                <div class="h-12 w-12 rounded-xl bg-orange-500/10 text-orange-500 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                </div>
                <h3 class="text-xl font-semibold text-white mb-2 group-hover:text-orange-400 transition-colors">Administrateur</h3>
                <p class="text-slate-400 text-sm flex-1">Accès complet au système, paramétrage, devis, factures et suivi global.</p>
                <div class="mt-4 flex items-center text-sm font-medium text-orange-500">
                    Accéder <svg class="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </div>
            </a>

            <!-- Client -->
            <a href="/customer" class="group relative flex flex-col bg-slate-800/50 backdrop-blur-md border border-slate-700/50 hover:border-blue-500/50 rounded-2xl p-6 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-blue-500/10 hover:bg-slate-800">
                <div class="h-12 w-12 rounded-xl bg-blue-500/10 text-blue-500 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
                <h3 class="text-xl font-semibold text-white mb-2 group-hover:text-blue-400 transition-colors">Espace Client</h3>
                <p class="text-slate-400 text-sm flex-1">Consultez vos devis, factures, et suivez l'avancement de vos chantiers.</p>
                <div class="mt-4 flex items-center text-sm font-medium text-blue-500">
                    Accéder <svg class="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </div>
            </a>

            <!-- Sous-Traitant -->
            <a href="/sous-traitant" class="group relative flex flex-col bg-slate-800/50 backdrop-blur-md border border-slate-700/50 hover:border-emerald-500/50 rounded-2xl p-6 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-emerald-500/10 hover:bg-slate-800">
                <div class="h-12 w-12 rounded-xl bg-emerald-500/10 text-emerald-500 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                </div>
                <h3 class="text-xl font-semibold text-white mb-2 group-hover:text-emerald-400 transition-colors">Sous-Traitant</h3>
                <p class="text-slate-400 text-sm flex-1">Portail de gestion des documents de vigilance et suivi des contrats.</p>
                <div class="mt-4 flex items-center text-sm font-medium text-emerald-500">
                    Accéder <svg class="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </div>
            </a>

            <!-- Salarié -->
            <a href="/salarie" class="group relative flex flex-col bg-slate-800/50 backdrop-blur-md border border-slate-700/50 hover:border-purple-500/50 rounded-2xl p-6 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-purple-500/10 hover:bg-slate-800">
                <div class="h-12 w-12 rounded-xl bg-purple-500/10 text-purple-500 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"></path></svg>
                </div>
                <h3 class="text-xl font-semibold text-white mb-2 group-hover:text-purple-400 transition-colors">Salarié</h3>
                <p class="text-slate-400 text-sm flex-1">Espace personnel, demandes de congés, fiches de paie et pointages.</p>
                <div class="mt-4 flex items-center text-sm font-medium text-purple-500">
                    Accéder <svg class="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </div>
            </a>

            <!-- Conducteur de travaux -->
            <a href="/terrain" class="group relative flex flex-col bg-slate-800/50 backdrop-blur-md border border-slate-700/50 hover:border-rose-500/50 rounded-2xl p-6 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-rose-500/10 hover:bg-slate-800">
                <div class="h-12 w-12 rounded-xl bg-rose-500/10 text-rose-500 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path></svg>
                </div>
                <h3 class="text-xl font-semibold text-white mb-2 group-hover:text-rose-400 transition-colors">Conducteur de Travaux</h3>
                <p class="text-slate-400 text-sm flex-1">Gestion terrain, pointages des équipes, logs journaliers et DOE.</p>
                <div class="mt-4 flex items-center text-sm font-medium text-rose-500">
                    Accéder <svg class="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </div>
            </a>

            <!-- Technicien SAV -->
            <a href="/technicien" class="group relative flex flex-col bg-slate-800/50 backdrop-blur-md border border-slate-700/50 hover:border-indigo-500/50 rounded-2xl p-6 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-indigo-500/10 hover:bg-slate-800">
                <div class="h-12 w-12 rounded-xl bg-indigo-500/10 text-indigo-500 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                </div>
                <h3 class="text-xl font-semibold text-white mb-2 group-hover:text-indigo-400 transition-colors">Technicien SAV</h3>
                <p class="text-slate-400 text-sm flex-1">Gestion des interventions, bons de travail, et signatures clients.</p>
                <div class="mt-4 flex items-center text-sm font-medium text-indigo-500">
                    Accéder <svg class="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </div>
            </a>

        </div>

        <div class="mt-16 text-center text-slate-500 text-sm">
            &copy; {{ date('Y') }} Batistack ERP. Tous droits réservés.
        </div>
    </div>
</body>
</html>
