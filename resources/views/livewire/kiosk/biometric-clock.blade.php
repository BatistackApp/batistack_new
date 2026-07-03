<div class="min-h-screen bg-gray-900 flex flex-col items-center justify-center p-4" x-data="biometricKiosk()">
    <script src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api/dist/face-api.min.js"></script>
    
    <div class="text-center mb-8">
        <h1 class="text-4xl font-bold text-white mb-2">Batistack Kiosque</h1>
        <p class="text-gray-400">Présentez votre visage devant la caméra pour pointer</p>
    </div>

    <div class="relative w-full max-w-3xl rounded-2xl overflow-hidden shadow-2xl bg-black aspect-video flex items-center justify-center border-4 border-gray-800">
        <!-- Webcam video -->
        <video x-ref="video" class="w-full h-full object-cover transform scale-x-[-1]" autoplay muted playsinline></video>
        
        <!-- Canvas for drawing face boxes -->
        <canvas x-ref="canvas" class="absolute top-0 left-0 w-full h-full transform scale-x-[-1]"></canvas>

        <!-- Loading overlay -->
        <div x-show="isLoading" class="absolute inset-0 bg-gray-900 flex flex-col items-center justify-center z-10 transition-opacity">
            <svg class="animate-spin h-12 w-12 text-blue-500 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="text-white text-xl font-medium" x-text="loadingText">Chargement de l'IA...</p>
        </div>
    </div>

    <!-- Logs -->
    <div class="mt-8 w-full max-w-2xl">
        <h3 class="text-gray-400 uppercase tracking-wider text-sm font-semibold mb-4 text-center">Derniers pointages</h3>
        <div class="space-y-3">
            @foreach($recentLogs as $log)
                <div class="bg-gray-800 rounded-xl p-4 flex items-center border border-gray-700 shadow-lg">
                    <div class="bg-green-500/20 text-green-400 p-3 rounded-full mr-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    </div>
                    <div>
                        <p class="text-white font-medium text-lg">{{ $log['message'] }}</p>
                        <p class="text-gray-500 text-sm">{{ $log['time'] }} - {{ $log['name'] }}</p>
                    </div>
                </div>
            @endforeach
            
            @if(empty($recentLogs))
                <div class="text-center text-gray-600 py-4">Aucun pointage récent.</div>
            @endif
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('biometricKiosk', () => ({
                isLoading: true,
                loadingText: 'Chargement des modèles IA...',
                employees: @json($employeesData),
                faceMatcher: null,
                isRecognizing: false,
                lastRecognizedId: null,
                lastRecognizedTime: 0,

                async init() {
                    try {
                        const MODEL_URL = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model';
                        await faceapi.nets.ssdMobilenetv1.loadFromUri(MODEL_URL);
                        await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
                        await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);

                        this.loadingText = 'Préparation de la base de données...';
                        this.initFaceMatcher();

                        this.loadingText = 'Démarrage de la caméra...';
                        await this.startVideo();

                        this.isLoading = false;
                        this.detectLoop();
                    } catch (e) {
                        console.error('Erreur IA:', e);
                        this.loadingText = 'Erreur: ' + e.message;
                    }
                },

                initFaceMatcher() {
                    const labeledDescriptors = [];
                    for (const emp of this.employees) {
                        if (emp.descriptor && emp.descriptor.length > 0) {
                            try {
                                const float32Array = new Float32Array(Object.values(emp.descriptor));
                                labeledDescriptors.push(new faceapi.LabeledFaceDescriptors(emp.id.toString(), [float32Array]));
                            } catch(e) {
                                console.error('Erreur parsing descripteur', emp.name);
                            }
                        }
                    }

                    if (labeledDescriptors.length > 0) {
                        this.faceMatcher = new faceapi.FaceMatcher(labeledDescriptors, 0.45);
                    }
                },

                async startVideo() {
                    const video = this.$refs.video;
                    try {
                        const stream = await navigator.mediaDevices.getUserMedia({ video: {} });
                        video.srcObject = stream;
                    } catch (err) {
                        console.error("Camera access denied", err);
                        this.loadingText = 'Veuillez autoriser la caméra.';
                    }
                },

                async detectLoop() {
                    const video = this.$refs.video;
                    const canvas = this.$refs.canvas;

                    if (!this.faceMatcher) {
                        console.log("Aucun modèle ou profil enregistré");
                        // On continue de tourner pour afficher la caméra, mais on ne match rien
                    }

                    video.addEventListener('play', async () => {
                        const displaySize = { width: video.videoWidth || 640, height: video.videoHeight || 480 };
                        faceapi.matchDimensions(canvas, displaySize);

                        setInterval(async () => {
                            if (this.isRecognizing || video.paused || video.ended) return;

                            const detections = await faceapi.detectAllFaces(video)
                                .withFaceLandmarks()
                                .withFaceDescriptors();

                            const resizedDetections = faceapi.resizeResults(detections, displaySize);
                            
                            canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);

                            if (this.faceMatcher) {
                                for (const detection of resizedDetections) {
                                    const bestMatch = this.faceMatcher.findBestMatch(detection.descriptor);
                                    
                                    const box = detection.detection.box;
                                    let labelText = 'Inconnu';
                                    let boxColor = 'red';

                                    if (bestMatch.label !== 'unknown') {
                                        const emp = this.employees.find(e => e.id.toString() === bestMatch.label);
                                        labelText = emp ? emp.name : bestMatch.label;
                                        boxColor = 'green';

                                        const now = Date.now();
                                        if (this.lastRecognizedId !== bestMatch.label || (now - this.lastRecognizedTime > 60000)) { // 1 min cooldown
                                            this.isRecognizing = true;
                                            this.lastRecognizedId = bestMatch.label;
                                            this.lastRecognizedTime = now;
                                            
                                            this.$wire.clockIn(bestMatch.label).then(() => {
                                                setTimeout(() => { this.isRecognizing = false; }, 2000);
                                            });
                                        }
                                    }

                                    const drawBox = new faceapi.draw.DrawBox(box, { label: labelText, boxColor: boxColor });
                                    drawBox.draw(canvas);
                                }
                            } else {
                                // Draw boxes without matching
                                faceapi.draw.drawDetections(canvas, resizedDetections);
                            }
                        }, 250); // 4 FPS
                    });
                }
            }));
        });
    </script>
</div>
