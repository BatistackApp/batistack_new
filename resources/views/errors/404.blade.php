<!DOCTYPE html>
<html lang="fr" class="antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Page Introuvable - Batistack</title>
    
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
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>

            <h1 class="text-4xl font-extrabold tracking-tight text-batistack-800 mb-4">
                Zone Introuvable
            </h1>
            
            <p class="text-lg text-batistack-500 mb-8 max-w-lg mx-auto leading-relaxed">
                La page que vous recherchez n'existe pas ou a été déplacée. Le plan du chantier a peut-être changé.
            </p>

            <a href="/" class="inline-flex items-center space-x-2 bg-batistack-700 hover:bg-batistack-800 text-white px-6 py-3 rounded-lg font-semibold transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9.707 14.707a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 1.414L7.414 9H15a1 1 0 110 2H7.414l2.293 2.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                </svg>
                <span>Retour à l'accueil</span>
            </a>
            
            <div class="mt-10 text-sm text-batistack-500">
                <p class="mt-2 text-xs font-mono text-batistack-400">Code HTTP : 404 - Not Found</p>
            </div>
        </div>
    </div>

</body>
</html>
