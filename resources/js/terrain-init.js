/**
 * Terrain Panel Init
 * Prefetches chantier data into IndexedDB for offline access and handles reconnection.
 */
document.addEventListener('DOMContentLoaded', async () => {
    if (!navigator.onLine) return;

    try {
        const db = new Dexie('batistack_terrain_cache');
        db.version(1).stores({
            chantiers: 'id',
            templates: 'id',
            cached_at: 'key',
        });

        // Prefetch chantiers
        const response = await fetch('/api/reserves/chantiers', {
            headers: { 'Accept': 'application/json' },
        });
        const data = await response.json();
        const chantiers = data.data || [];

        await db.chantiers.clear();
        for (const c of chantiers) {
            await db.chantiers.put(c);
        }
        await db.cached_at.put({ key: 'chantiers', value: new Date().toISOString() });

        // Prefetch checklist templates
        const tplResponse = await fetch('/api/checklist/templates', {
            headers: { 'Accept': 'application/json' },
        });
        const tplData = await tplResponse.json();
        const templates = tplData.data || [];
        await db.templates.clear();
        for (const t of templates) {
            await db.templates.put(t);
        }
        await db.cached_at.put({ key: 'templates', value: new Date().toISOString() });
    } catch (e) {
        console.log('Prefetch cache failed:', e);
    }

    // Reconnection handler
    let wasOffline = !navigator.onLine;
    window.addEventListener('online', () => {
        if (wasOffline) {
            showToast('Connexion rétablie — synchronisation en cours...');
            window.dispatchEvent(new CustomEvent('terrain:reconnect'));
        }
        wasOffline = false;
    });
    window.addEventListener('offline', () => {
        wasOffline = true;
    });

    // Handle SW sync failures (CSRF token expiration)
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.addEventListener('message', (event) => {
            if (event.data?.type === 'SYNC_FAILED') {
                showToast(event.data.message || 'Erreur de synchronisation. Rafraîchissez la page.', 'error');
            }
        });
    }

    function showToast(message, type = 'info') {
        const toast = document.createElement('div');
        const bgColor = type === 'error' ? 'bg-red-600' : 'bg-emerald-600';
        toast.className = `fixed bottom-4 right-4 z-[9999] px-4 py-3 ${bgColor} text-white rounded-lg shadow-lg flex items-center gap-2 transition-opacity`;
        toast.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg><span>' + message + '</span>';
        document.body.appendChild(toast);
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }
});
