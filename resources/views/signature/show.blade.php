<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signature du Document</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
</head>
<body class="bg-gray-100 min-h-screen font-sans">
    <div class="max-w-4xl mx-auto p-4 md:p-8">
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="p-6 bg-blue-600 text-white flex justify-between items-center">
                <h1 class="text-2xl font-bold">Demande de Signature</h1>
                <span class="px-3 py-1 bg-blue-800 rounded-full text-sm">Document sécurisé</span>
            </div>

            @if(session('success'))
                <div class="p-4 bg-green-100 text-green-700 font-medium">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="p-4 bg-red-100 text-red-700 font-medium">
                    {{ session('error') }}
                </div>
            @endif

            <div class="p-6">
                <div class="mb-8 border rounded-lg overflow-hidden bg-gray-50 h-[500px]">
                    @if($documentUrl)
                        <iframe src="{{ $documentUrl }}" class="w-full h-full" frameborder="0"></iframe>
                    @else
                        <div class="flex items-center justify-center h-full text-gray-400">
                            Aperçu du document non disponible
                        </div>
                    @endif
                </div>

                <form action="{{ route('signature.sign', $signature->token) }}" method="POST" id="signature-form">
                    @csrf
                    <input type="hidden" name="signature_data" id="signature-data">
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 font-semibold mb-2">Veuillez signer ci-dessous :</label>
                        <div class="border-2 border-gray-300 rounded-lg bg-white overflow-hidden" style="touch-action: none;">
                            <canvas id="signature-pad" class="w-full h-64 cursor-crosshair"></canvas>
                        </div>
                        <div class="flex justify-between items-center mt-2">
                            <span class="text-sm text-gray-500">Tracez votre signature dans ce cadre</span>
                            <button type="button" id="clear" class="text-sm text-blue-600 hover:text-blue-800">Effacer</button>
                        </div>
                    </div>

                    <div class="mt-8 flex justify-end">
                        <button type="submit" id="submit-btn" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg shadow-md transition duration-200">
                            Signer le document
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const canvas = document.getElementById('signature-pad');
        const signaturePad = new SignaturePad(canvas, {
            backgroundColor: 'rgba(255, 255, 255, 1)',
            penColor: 'rgb(0, 0, 0)'
        });

        // Resize canvas to fix coordinate offset issues
        function resizeCanvas() {
            const ratio =  Math.max(window.devicePixelRatio || 1, 1);
            canvas.width = canvas.offsetWidth * ratio;
            canvas.height = canvas.offsetHeight * ratio;
            canvas.getContext("2d").scale(ratio, ratio);
            signaturePad.clear();
        }

        window.onresize = resizeCanvas;
        resizeCanvas();

        document.getElementById('clear').addEventListener('click', function () {
            signaturePad.clear();
        });

        document.getElementById('signature-form').addEventListener('submit', function (e) {
            if (signaturePad.isEmpty()) {
                e.preventDefault();
                alert('Veuillez signer le document avant de valider.');
            } else {
                const dataUrl = signaturePad.toDataURL('image/png');
                document.getElementById('signature-data').value = dataUrl;
            }
        });
    </script>
</body>
</html>
