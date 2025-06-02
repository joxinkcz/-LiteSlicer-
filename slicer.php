<?php
require_once __DIR__.'/includes/Config.php';
require_once __DIR__.'/includes/Auth.php';
require_once __DIR__.'/includes/FileUploader.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$modelFile = $_GET['model'] ?? '';
$modelPath = MODEL_UPLOAD_DIR . $modelFile;

if (!file_exists($modelPath)) {
    header("Location: dashboard.php");
    exit;
}

$modelInfo = FileUploader::getModelInfo($modelFile);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lite Slicer - 3D Slicer</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #0abdc6;
            --dark-blue: #1a237e;
            --light-blue: #e3f2fd;
            --accent-blue: #64b5f6;
            --taext-dark: #212121;
            --text-light: #f5f5f5;
            --card-bg: rgba(26, 35, 126, 0.7);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto', sans-serif;
            background-color: #0c0c1a;
            color: var(--text-light);
            height: 100vh;
            overflow: hidden;
        }

        #matrix-effect {
            position: fixed;
            top: 0;
            left: 0;
            z-index: -1;
            opacity: 0.1;
            width: 100%;
            height: 100%;
        }

        .app-header {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            background: var(--card-bg);
            border-bottom: 1px solid var(--primary-blue);
            backdrop-filter: blur(5px);
        }

        .logo {
            display: flex;
            align-items: center;
        }

        .logo img {
            height: 30px;
            margin-right: 10px;
        }

        .logo-text {
            font-size: 1.2rem;
            font-weight: 700;
            background: linear-gradient(90deg, #64b5f6, #0abdc6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .user-menu {
            margin-left: auto;
            display: flex;
            align-items: center;
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--primary-blue);
            color: var(--text-dark);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            margin-right: 10px;
        }

        .main-container {
            display: grid;
            grid-template-columns: 300px 1fr;
            height: calc(100vh - 62px);
        }

        .sidebar {
            background: var(--card-bg);
            border-right: 1px solid var(--primary-blue);
            padding: 20px;
            overflow-y: auto;
            backdrop-filter: blur(5px);
        }

        .section {
            margin-bottom: 30px;
        }

        .section-title {
            color: var(--primary-blue);
            font-size: 1.1rem;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 1px solid rgba(100, 181, 246, 0.3);
        }

        .model-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .info-item {
            margin-bottom: 12px;
        }

        .info-label {
            color: var(--accent-blue);
            font-size: 0.8rem;
            margin-bottom: 4px;
        }

        .info-value {
            font-size: 0.9rem;
            word-break: break-all;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-label {
            display: block;
            color: var(--accent-blue);
            font-size: 0.9rem;
            margin-bottom: 6px;
        }

        .form-control {
            width: 100%;
            padding: 8px 12px;
            background: rgba(10, 189, 198, 0.1);
            border: 1px solid rgba(100, 181, 246, 0.3);
            border-radius: 5px;
            color: var(--text-light);
            font-family: inherit;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 2px rgba(10, 189, 198, 0.2);
        }

        .slider-container {
            margin-bottom: 20px;
        }

        .slider-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            color: var(--accent-blue);
            font-size: 0.9rem;
        }

        .slider {
            width: 100%;
            height: 6px;
            -webkit-appearance: none;
            background: linear-gradient(90deg, var(--primary-blue), var(--accent-blue));
            border-radius: 3px;
            outline: none;
        }

        .slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: var(--primary-blue);
            cursor: pointer;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
        }

        .checkbox-group input {
            margin-right: 8px;
            accent-color: var(--primary-blue);
        }

        .btn {
            width: 100%;
            padding: 10px;
            border: none;
            border-radius: 5px;
            background: linear-gradient(90deg, var(--primary-blue), var(--accent-blue));
            color: var(--text-dark);
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn:hover {
            box-shadow: 0 0 15px rgba(10, 189, 198, 0.5);
        }

        .gcode-terminal {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid var(--primary-blue);
            border-radius: 5px;
            padding: 12px;
            height: 200px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
            color: var(--accent-blue);
            line-height: 1.4;
        }

        .terminal-line {
            margin-bottom: 5px;
        }

        .terminal-line::before {
            content: ">";
            margin-right: 5px;
            color: var(--primary-blue);
        }

        .download-btn {
            width: 100%;
            padding: 10px;
            margin-top: 15px;
            border: none;
            border-radius: 5px;
            background: var(--primary-blue);
            color: var(--text-dark);
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
        }

        .download-btn:hover {
            box-shadow: 0 0 15px rgba(10, 189, 198, 0.5);
        }

        .download-btn.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            background: #555;
        }

        .loading {
            display: none;
            text-align: center;
            color: var(--primary-blue);
            margin: 15px 0;
        }

        .spinner {
            display: inline-block;
            width: 18px;
            height: 18px;
            border: 3px solid rgba(100, 181, 246, 0.3);
            border-radius: 50%;
            border-top-color: var(--primary-blue);
            animation: spin 1s linear infinite;
            margin-right: 8px;
            vertical-align: middle;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .viewer-container {
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .viewer-toolbar {
            padding: 10px 15px;
            background: var(--card-bg);
            border-bottom: 1px solid var(--primary-blue);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .viewer-title {
            color: var(--primary-blue);
            font-weight: 500;
        }

        .toolbar-actions {
            display: flex;
            gap: 8px;
        }

        .toolbar-btn {
            background: rgba(10, 189, 198, 0.2);
            border: 1px solid var(--primary-blue);
            color: var(--primary-blue);
            padding: 6px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.85rem;
            transition: all 0.3s;
        }

        .toolbar-btn:hover {
            background: var(--primary-blue);
            color: var(--text-dark);
        }

        #viewer-wrapper {
            flex: 1;
            position: relative;
        }

        #viewer {
            width: 100%;
            height: 100%;
        }

        .control-panel {
            position: absolute;
            bottom: 15px;
            right: 15px;
            z-index: 100;
        }

        .control-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: rgba(0, 0, 0, 0.7);
            border: 1px solid var(--primary-blue);
            color: var(--primary-blue);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s;
            margin-bottom: 5px;
        }

        .control-btn:hover {
            background: var(--primary-blue);
            color: var(--text-dark);
            transform: scale(1.1);
        }

        .control-group {
            display: flex;
            gap: 5px;
            margin-bottom: 5px;
        }

        .rotate-btn {
            background: rgba(10, 189, 198, 0.2);
            border: 1px solid var(--primary-blue);
            color: var(--primary-blue);
            padding: 6px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.85rem;
            transition: all 0.3s;
        }

        .rotate-btn:hover {
            background: var(--primary-blue);
            color: var(--text-dark);
        }
    </style>
</head>
<body>
<div id="matrix-effect"></div>

<header class="app-header">
    <div class="logo">
        <img src="photo/photo1.jpg" alt="Lite Slicer Logo">
        <div class="logo-text">Lite Slicer</div>
    </div>
    <div class="user-menu">
        <div class="user-avatar"><?= strtoupper(substr($auth->getCurrentUser()['username'], 0, 1)) ?></div>
        <a href="dashboard.php" class="toolbar-btn">Dashboard</a>
    </div>
</header>

<div class="main-container">
    <div class="sidebar">
        <div class="section">
            <h3 class="section-title">MODEL INFORMATION</h3>
            <div class="model-info-grid">
                <div class="info-item">
                    <div class="info-label">File Name</div>
                    <div class="info-value"><?= htmlspecialchars($modelInfo['filename']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">File Size</div>
                    <div class="info-value"><?= htmlspecialchars($modelInfo['size']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Dimensions</div>
                    <div class="info-value" id="model-dimensions"><?= htmlspecialchars($modelInfo['dimensions']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Volume</div>
                    <div class="info-value" id="model-volume"><?= htmlspecialchars($modelInfo['volume']) ?></div>
                </div>
            </div>
        </div>

        <div class="section">
            <h3 class="section-title">SLICING SETTINGS</h3>
            <form id="slice-form">
                <input type="hidden" name="model" value="<?= htmlspecialchars($modelFile) ?>">

                <div class="form-group">
                    <label class="form-label">Material</label>
                    <select name="material" class="form-control">
                        <option value="PLA" selected>PLA</option>
                        <option value="ABS">ABS</option>
                        <option value="PETG">PETG</option>
                        <option value="TPU">TPU</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Nozzle Size (mm)</label>
                    <select name="nozzle_size" class="form-control">
                        <option value="0.2">0.2</option>
                        <option value="0.4" selected>0.4</option>
                        <option value="0.6">0.6</option>
                        <option value="0.8">0.8</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Layer Height (mm)</label>
                    <input type="number" name="layer_height" min="0.05" max="0.3" step="0.05" value="0.2" class="form-control">
                </div>

                <div class="slider-container">
                    <div class="slider-label">
                        <span>Infill Density</span>
                        <span id="infill-value">20%</span>
                    </div>
                    <input type="range" id="infill-density" name="infill_density" min="0" max="100" value="20" class="slider">
                </div>

                <div class="checkbox-group">
                    <input type="checkbox" id="generate-support" name="generate_support">
                    <label for="generate-support">Generate Support</label>
                </div>

                <div class="checkbox-group">
                    <input type="checkbox" id="add-brim" name="add_brim">
                    <label for="add-brim">Add Brim</label>
                </div>

                <button type="submit" id="slice-btn" class="btn">SLICE MODEL</button>
            </form>
        </div>
        <div class="scale-controls">

        </div> <button onclick="scaleModel(1.1)">Увеличить (+)</button>
        <button onclick="scaleModel(0.9)">Уменьшить (-)</button>
        <button onclick="resetModelScale()">Сбросить масштаб</button>
        <div class="section">
            <h3 class="section-title">G-CODE OUTPUT</h3>
            <div class="gcode-terminal" id="gcode-terminal">
                <div class="terminal-line">Ready to slice model...</div>
            </div>
            <button id="download-btn" class="download-btn disabled">DOWNLOAD G-CODE</button>
            <div class="loading" id="loading">
                <span class="spinner"></span>
                <span>Processing...</span>
            </div>
        </div>
    </div>

    <div class="viewer-container">
        <div class="viewer-toolbar">
            <div class="viewer-title">3D MODEL VIEWER</div>
            <div class="toolbar-actions">
                <button class="toolbar-btn" id="reset-view">Reset View</button>
                <button class="rotate-btn" id="rotate-left" title="Rotate Left">←</button>
                <button class="rotate-btn" id="rotate-right" title="Rotate Right">→</button>
                <button class="rotate-btn" id="rotate-up" title="Rotate Up">↑</button>
                <button class="rotate-btn" id="rotate-down" title="Rotate Down">↓</button>
            </div>
        </div>
        <div id="viewer-wrapper">
            <div id="viewer"></div>
            <div class="control-panel">
                <div class="control-group">
                    <button class="control-btn" id="zoom-in" title="Zoom In">+</button>
                    <button class="control-btn" id="zoom-out" title="Zoom Out">-</button>
                </div>
                <div class="control-group">
                    <button class="control-btn" id="move-x-plus" title="Move X+">+X</button>
                    <button class="control-btn" id="move-x-minus" title="Move X-">-X</button>
                </div>
                <div class="control-group">
                    <button class="control-btn" id="move-y-plus" title="Move Y+">+Y</button>
                    <button class="control-btn" id="move-y-minus" title="Move Y-">-Y</button>
                </div>
                <div class="control-group">
                    <button class="control-btn" id="move-z-plus" title="Move Z+">+Z</button>
                    <button class="control-btn" id="move-z-minus" title="Move Z-">-Z</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/three@0.132.2/build/three.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/three@0.132.2/examples/js/controls/OrbitControls.js"></script>
<script src="https://cdn.jsdelivr.net/npm/three@0.132.2/examples/js/loaders/STLLoader.js"></script>
<script>
    // Matrix effect
    document.addEventListener('DOMContentLoaded', function() {
        const canvas = document.createElement('canvas');
        const container = document.getElementById('matrix-effect');
        container.appendChild(canvas);
        const ctx = canvas.getContext('2d');
        canvas.width = container.offsetWidth;
        canvas.height = container.offsetHeight;

        const chars = "01アイウエオカキクケコサシスセソタチツテトナニヌネノハヒフヘホマミムメモヤユヨラリルレロワヲン";
        const fontSize = 14;
        const columns = canvas.width / fontSize;
        const drops = [];

        for (let i = 0; i < columns; i++) {
            drops[i] = Math.random() * canvas.height;
        }

        function draw() {
            ctx.fillStyle = 'rgba(0, 0, 0, 0.05)';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.fillStyle = '#0abdc6';
            ctx.font = fontSize + 'px monospace';

            for (let i = 0; i < drops.length; i++) {
                const text = chars[Math.floor(Math.random() * chars.length)];
                ctx.fillText(text, i * fontSize, drops[i] * fontSize);

                if (drops[i] * fontSize > canvas.height && Math.random() > 0.975) {
                    drops[i] = 0;
                }
                drops[i]++;
            }
        }

        setInterval(draw, 33);
    });

    // 3D Viewer implementation
    let scene, camera, renderer, controls, model;
    let modelCenter = new THREE.Vector3();
    const ROTATION_STEP = 0.1; // Rotation step in radians

    function initScene() {
        const container = document.getElementById('viewer-wrapper');

        // Scene setup
        scene = new THREE.Scene();
        scene.background = new THREE.Color(0x111126);

        // Camera setup
        camera = new THREE.PerspectiveCamera(
            75,
            container.clientWidth / container.clientHeight,
            0.1,
            1000
        );
        camera.position.z = 5;

        // Renderer setup
        renderer = new THREE.WebGLRenderer({ antialias: true });
        renderer.setPixelRatio(window.devicePixelRatio);
        renderer.setSize(container.clientWidth, container.clientHeight);
        renderer.shadowMap.enabled = true;
        document.getElementById('viewer').appendChild(renderer.domElement);

        // Lighting
        const ambientLight = new THREE.AmbientLight(0x404040);
        scene.add(ambientLight);

        const directionalLight = new THREE.DirectionalLight(0xffffff, 0.8);
        directionalLight.position.set(1, 1, 1);
        directionalLight.castShadow = true;
        scene.add(directionalLight);

        // Grid helper
        const gridHelper = new THREE.GridHelper(200, 50, 0x003366, 0x002244);
        gridHelper.position.y = -0.5;
        scene.add(gridHelper);

        // Load model
        loadModel(`/uploads/models/<?= $modelFile ?>`);

        // Controls
        controls = new THREE.OrbitControls(camera, renderer.domElement);
        controls.enableDamping = true;
        controls.dampingFactor = 0.05;
        controls.screenSpacePanning = false;
        controls.maxPolarAngle = Math.PI;
        controls.minPolarAngle = 0;
        controls.enablePan = true;

        // Event listeners
        setupEventListeners();

        // Handle window resize
        window.addEventListener('resize', onWindowResize);

        // Start animation loop
        animate();
    }

    function loadModel(url) {
        const loader = new THREE.STLLoader();
        loader.load(url, function(geometry) {
            if (model) scene.remove(model);

            // Create material with neon blue color
            const material = new THREE.MeshPhongMaterial({
                color: 0x0abdc6,
                specular: 0x111111,
                shininess: 30,
                side: THREE.DoubleSide,
                flatShading: true
            });

            model = new THREE.Mesh(geometry, material);
            model.castShadow = true;

            // Center and scale model
            geometry.computeBoundingBox();
            const boundingBox = geometry.boundingBox;
            boundingBox.getCenter(modelCenter);
            model.position.sub(modelCenter);

            const size = boundingBox.getSize(new THREE.Vector3());
            const maxDim = Math.max(size.x, size.y, size.z);
            const scale = 5 / maxDim;
            model.scale.set(scale, scale, scale);

            scene.add(model);

            // Update model info
            document.getElementById('model-dimensions').textContent =
                `${(size.x * scale).toFixed(1)} × ${(size.y * scale).toFixed(1)} × ${(size.z * scale).toFixed(1)} mm`;

            const volume = (size.x * size.y * size.z * scale * scale * scale / 1000).toFixed(1);
            document.getElementById('model-volume').textContent = `${volume} cm³`;

            // Adjust camera to fit model
            fitCameraToModel(boundingBox, scale);
        }, undefined, function(error) {
            console.error('Error loading model:', error);
            const terminal = document.getElementById('gcode-terminal');
            terminal.innerHTML = '';
            const errorLine = document.createElement('div');
            errorLine.className = 'terminal-line';
            errorLine.style.color = '#ff5252';
            errorLine.textContent = `> ERROR: Failed to load model (${error})`;
            terminal.appendChild(errorLine);
        });
    }

    function fitCameraToModel(boundingBox, scale) {
        const size = boundingBox.getSize(new THREE.Vector3());
        const center = boundingBox.getCenter(new THREE.Vector3());
        const maxDim = Math.max(size.x, size.y, size.z);
        const fov = camera.fov * (Math.PI / 180);
        let cameraZ = Math.abs(maxDim * scale / Math.sin(fov / 2)) * 1.1;
        cameraZ = Math.max(cameraZ, maxDim * scale * 0.5);

        camera.position.z = cameraZ;
        camera.lookAt(center);
        controls.target.copy(center);
        controls.update();
    }

    function onWindowResize() {
        const container = document.getElementById('viewer-wrapper');
        camera.aspect = container.clientWidth / container.clientHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(container.clientWidth, container.clientHeight);
    }

    function animate() {
        requestAnimationFrame(animate);
        controls.update();
        renderer.render(scene, camera);
    }

    function setupEventListeners() {
        // Infill slider
        document.getElementById('infill-density').addEventListener('input', function() {
            document.getElementById('infill-value').textContent = `${this.value}%`;
        });

        // Zoom buttons
        document.getElementById('zoom-in').addEventListener('click', () => {
            camera.zoom *= 1.2;
            camera.updateProjectionMatrix();
        });

        document.getElementById('zoom-out').addEventListener('click', () => {
            camera.zoom /= 1.2;
            camera.updateProjectionMatrix();
        });

        // Move buttons (positive and negative directions)
        ['x', 'y', 'z'].forEach(axis => {
            // Positive direction
            document.getElementById(`move-${axis}-plus`).addEventListener('click', () => {
                if (model) {
                    const position = new THREE.Vector3();
                    position[axis] = 0.5;
                    model.position.add(position);
                }
            });

            // Negative direction
            document.getElementById(`move-${axis}-minus`).addEventListener('click', () => {
                if (model) {
                    const position = new THREE.Vector3();
                    position[axis] = -0.5;
                    model.position.add(position);
                }
            });
        });

        // Rotation buttons
        document.getElementById('rotate-left').addEventListener('click', () => {
            if (model) {
                model.rotation.y += ROTATION_STEP;
            }
        });

        document.getElementById('rotate-right').addEventListener('click', () => {
            if (model) {
                model.rotation.y -= ROTATION_STEP;
            }
        });

        document.getElementById('rotate-up').addEventListener('click', () => {
            if (model) {
                model.rotation.x += ROTATION_STEP;
            }
        });

        document.getElementById('rotate-down').addEventListener('click', () => {
            if (model) {
                model.rotation.x -= ROTATION_STEP;
            }
        });

        // Reset view
        document.getElementById('reset-view').addEventListener('click', () => {
            if (model) {
                controls.reset();
                camera.zoom = 1;
                camera.updateProjectionMatrix();
                model.rotation.set(0, 0, 0);
                camera.lookAt(modelCenter);
                controls.target.copy(modelCenter);
                controls.update();
            }
        });

        // Slice form submission
        document.getElementById('slice-form').addEventListener('submit', function(e) {
            e.preventDefault();

            // Show loading state
            document.getElementById('loading').style.display = 'block';
            document.getElementById('slice-btn').disabled = true;

            const terminal = document.getElementById('gcode-terminal');
            terminal.innerHTML = '';
            const statusLine = document.createElement('div');
            statusLine.className = 'terminal-line';
            statusLine.style.color = 'var(--primary-blue)';
            statusLine.textContent = '> Starting slicing process...';
            terminal.appendChild(statusLine);

            // Collect form data
            const formData = new FormData(this);

            fetch('/slice.php', {
                method: 'POST',
                body: formData
            })
                .then(response => {
                    if (!response.ok) throw new Error('Network error');
                    return response.json();
                })
                .then(data => {
                    document.getElementById('loading').style.display = 'none';
                    document.getElementById('slice-btn').disabled = false;

                    if (data.success) {
                        // Clear terminal
                        terminal.innerHTML = '';

                        // Show G-code preview
                        const lines = data.gcode.split('\n');
                        const previewLines = lines.slice(0, 60);

                        previewLines.forEach(line => {
                            const lineElement = document.createElement('div');
                            lineElement.className = 'terminal-line';
                            lineElement.textContent = line;
                            terminal.appendChild(lineElement);
                        });

                        // Add info about full file
                        const infoLine = document.createElement('div');
                        infoLine.className = 'terminal-line';
                        infoLine.style.color = 'var(--primary-blue)';
                        infoLine.textContent = `> G-code generated (${lines.length} lines total)`;
                        terminal.appendChild(infoLine);

                        // Add print info
                        const printInfo = document.createElement('div');
                        printInfo.className = 'terminal-line';
                        printInfo.textContent = `> Estimated print time: ${data.print_time}`;
                        terminal.appendChild(printInfo);

                        const filamentInfo = document.createElement('div');
                        filamentInfo.className = 'terminal-line';
                        filamentInfo.textContent = `> Filament used: ${data.filament_used}`;
                        terminal.appendChild(filamentInfo);

                        // Enable download button
                        const downloadBtn = document.getElementById('download-btn');
                        downloadBtn.classList.remove('disabled');
                        downloadBtn.onclick = () => {
                            window.location.href = `/download.php?file=${encodeURIComponent(data.filename)}`;
                        };
                    } else {
                        showError(data.error || 'Unknown error occurred');
                    }
                })
                .catch(error => {
                    document.getElementById('loading').style.display = 'none';
                    document.getElementById('slice-btn').disabled = false;
                    showError(error.message);
                });

            function showError(message) {
                const errorLine = document.createElement('div');
                errorLine.className = 'terminal-line';
                errorLine.style.color = '#ff5252';
                errorLine.textContent = `> ERROR: ${message}`;
                terminal.appendChild(errorLine);
            }
        });
    }

    // Initialize
    document.addEventListener('DOMContentLoaded', initScene);
</script>
</body>
</html>