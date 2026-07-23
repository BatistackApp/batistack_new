<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
            return;
        }
        
        navigator.serviceWorker.register('/sw.js').then(registration => {
            // Demande la permission webpush (seulement si le statut est par défaut)
            if (Notification.permission === 'default') {
                Notification.requestPermission().then(permission => {
                    if (permission === 'granted') {
                        subscribeUser(registration);
                    }
                });
            } else if (Notification.permission === 'granted') {
                // On met à jour l'abonnement au cas où
                subscribeUser(registration);
            }
        });

        function subscribeUser(registration) {
            const vapidPublicKey = '{{ env("VAPID_PUBLIC_KEY") }}';
            if (!vapidPublicKey) return;

            const convertedVapidKey = urlBase64ToUint8Array(vapidPublicKey);

            registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: convertedVapidKey
            }).then(subscription => {
                // Envoi au serveur
                fetch('/webpush/subscribe', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(subscription)
                }).catch(err => console.error('Failed to subscribe on server: ', err));
            }).catch(err => {
                console.error('Failed to subscribe to Push Service: ', err);
            });
        }

        function urlBase64ToUint8Array(base64String) {
            const padding = '='.repeat((4 - base64String.length % 4) % 4);
            const base64 = (base64String + padding)
                .replace(/\-/g, '+')
                .replace(/_/g, '/');
            const rawData = window.atob(base64);
            const outputArray = new Uint8Array(rawData.length);
            for (let i = 0; i < rawData.length; ++i) {
                outputArray[i] = rawData.charCodeAt(i);
            }
            return outputArray;
        }
    });
</script>
