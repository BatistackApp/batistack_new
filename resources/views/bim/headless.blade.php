<!DOCTYPE html>
<html>
<head>
    <title>BIM Headless</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-900 m-0 p-0 overflow-hidden" style="width: 800px; height: 600px;">
    <div id="viewer-container" style="width: 100%; height: 100%;"></div>
    
    <script type="module">
        const format = '{{ strtolower($model->format) }}';
        const url = '{{ Storage::disk("public")->url($model->file_path) }}';
        const container = document.getElementById('viewer-container');
        
        async function init() {
            if (format === 'ifc') {
                const viewer = new window.IfcViewerAPI({ container, backgroundColor: new window.THREE.Color(0x111827) });
                await viewer.IFC.setWasmPath('/');
                const ifcModel = await viewer.IFC.loadIfcUrl(url);
                viewer.shadowDropper.renderShadow(ifcModel.modelID);
            } else if (format === 'dxf') {
                const viewer = new window.DxfViewer(container, {
                    autoResize: true,
                    clearColor: new window.THREE.Color(0x111827),
                });
                await viewer.Load({ url });
            }
        }
        
        window.addEventListener('load', () => {
            setTimeout(init, 500);
        });
    </script>
</body>
</html>
