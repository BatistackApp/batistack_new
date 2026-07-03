<div class="min-h-screen bg-gray-900 flex flex-col items-center justify-center p-4" x-data="biometricEnrollment()">
    <script src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api/dist/face-api.min.js"></script>

    <div class="w-full max-w-4xl grid grid-cols-1 md:grid-cols-2 gap-8">
        
        <!-- Left: Webcam -->
        <div class="flex flex-col items-center">
            <h1 class="text-3xl font-bold text-white mb-6">Enrôlement Biométrique</h1>
            
            <div class="relative w-full rounded-2xl overflow-hidden shadow-2xl bg-black aspect-square flex items-center justify-center border-4 border-gray-800">
                <video x-ref="video" class="w-full h-full object-cover transform scale-x-[-1]" autoplay muted playsinline></video>
                <canvas x-ref="canvas" class="absolute top-0 left-0 w-full h-full transform scale-x-[-1]"></canvas>

                <div x-show="isLoading" class="absolute inset-0 bg-gray-900 flex flex-col items-center justify-center z-10 transition-opacity">
                    <svg class="animate-spin h-10 w-10 text-blue-500 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p class="text-white font-medium" x-text="loadingText">Chargement de l'IA...</p>
                </div>
            </div>
            
            <p class="text-gray-400 mt-4 text-center">Placez-vous au centre de l'image jusqu'à ce que le cadre devienne vert.</p>
        </div>

        <!-- Right: Form -->
        <div class="flex flex-col justify-center bg-gray-800 p-8 rounded-2xl shadow-xl">
            @if($isEnrolled)
                <div class="text-center">
                    <div class="inline-flex items-center justify-center w-20 h-20 bg-green-500 rounded-full mb-6">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    </div>
                    <h2 class="text-2xl font-bold text-white mb-2">Enrôlement Terminé !</h2>
                    <p class="text-gray-400 mb-6">Le profil biométrique a été enregistré avec succès.</p>
                    <button wire:click="$set('isEnrolled', false)" class="px-6 py-3 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition">
                        Enrôler une autre personne
                    </button>
                </div>
            @else
                <div class="mb-6">
                    <label class="block text-gray-300 text-sm font-bold mb-2" for="employee">
                        1. Sélectionnez le collaborateur
                    </label>
                    <select wire:model.live="selectedEmployeeId" id="employee" class="w-full bg-gray-700 border border-gray-600 text-white rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Choisir un collaborateur --</option>
                        @foreach($employeesList as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-8" x-show="$wire.selectedEmployeeId">
                    <label class="block text-gray-300 text-sm font-bold mb-2">
                        2. Capture de l'empreinte faciale
                    </label>
                    <button @click="captureAndEnroll()" :disabled="!isReadyToCapture" :class="{'bg-green-600 hover:bg-green-700': isReadyToCapture, 'bg-gray-600 cursor-not-allowed': !isReadyToCapture}" class="w-full text-white font-bold py-4 px-4 rounded-lg flex items-center justify-center transition">
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        <span x-text="isReadyToCapture ? 'Capturer et Enregistrer' : 'Recherche du visage...'"></span>
                    </button>
                </div>
            @endif
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('biometricEnrollment', () => ({
                isLoading: true,
                loadingText: 'Chargement des modèles IA...',
                isReadyToCapture: false,
                currentDescriptor: null,

                async init() {
                    try {
                        const MODEL_URL = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model';
                        await faceapi.nets.ssdMobilenetv1.loadFromUri(MODEL_URL);
                        await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
                        await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);

                        this.loadingText = 'Démarrage de la caméra...';
                        await this.startVideo();

                        this.isLoading = false;
                        this.detectLoop();
                    } catch (e) {
                        console.error('Erreur IA:', e);
                        this.loadingText = 'Erreur: ' + e.message;
                    }
                },

                async startVideo() {
                    const video = this.$refs.video;
                    try {
                        const stream = await navigator.mediaDevices.getUserMedia({ video: true });
                        video.srcObject = stream;
                    } catch (err) {
                        console.error("Camera access denied", err);
                        this.loadingText = 'Veuillez autoriser la caméra.';
                    }
                },

                async detectLoop() {
                    const video = this.$refs.video;
                    const canvas = this.$refs.canvas;

                    video.addEventListener('play', async () => {
                        const displaySize = { width: video.videoWidth || 640, height: video.videoHeight || 480 };
                        faceapi.matchDimensions(canvas, displaySize);

                        setInterval(async () => {
                            if (video.paused || video.ended || this.$wire.isEnrolled) return;

                            const detections = await faceapi.detectSingleFace(video)
                                .withFaceLandmarks()
                                .withFaceDescriptor();

                            canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);

                            if (detections) {
                                const resizedDetections = faceapi.resizeResults(detections, displaySize);
                                
                                // Draw a green box to indicate readiness
                                const box = resizedDetections.detection.box;
                                const drawBox = new faceapi.draw.DrawBox(box, { label: 'Visage Détecté', boxColor: 'green' });
                                drawBox.draw(canvas);

                                this.currentDescriptor = Array.from(detections.descriptor); // Convert Float32Array to JS array
                                this.isReadyToCapture = true;
                            } else {
                                this.isReadyToCapture = false;
                                this.currentDescriptor = null;
                            }
                        }, 200);
                    });
                },

                captureAndEnroll() {
                    if (this.isReadyToCapture && this.currentDescriptor && this.$wire.selectedEmployeeId) {
                        this.$wire.enroll(this.currentDescriptor);
                    }
                }
            }));
        });
    </script>
</div>
