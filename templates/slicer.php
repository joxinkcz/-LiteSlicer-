<?php
require_once __DIR__.'/../includes/Config.php';
require_once __DIR__.'/../includes/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('HTTP/1.1 403 Forbidden');
    die(json_encode(['success' => false, 'error' => 'Not authorized']));
}

$modelFile = $_GET['model'] ?? '';
$modelPath = MODEL_UPLOAD_DIR . $modelFile;

if (!file_exists($modelPath)) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NEON SLICER 3000 | 3D Control</title>
    <style>
        :root {
            --neon-red: #07ff8f;
            --neon-blue: #0abdc6;
            --neon-purple: #0004d3;
            --neon-yellow: #f5d742;
            --dark-bg: #0c0c1a;
        }
        body {
            margin: 0;
            background-color: var(--dark-bg);
            color: #e0e0e0;
            font-family: 'Rajdhani', sans-serif;
            overflow: hidden;
        }
        #viewer-container {
            position: relative;
            width: 100%;
            height: 70vh;
            border: 1px solid var(--neon-blue);
        }
        #controls-overlay {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(20, 20, 40, 0.9);
            border: 1px solid var(--neon-purple);
            padding: 15px;
            z-index: 1000;
            width: 280px;
        }
        .control-group {
            margin-bottom: 15px;
        }
        .control-title {
            color: var(--neon-yellow);
            margin-bottom: 8px;
            font-size: 0.9rem;
        }
        .axis-controls {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }
        .control-btn {
            background: rgba(0,0,0,0.7);
            border: 1px solid var(--neon-blue);
            color: var(--neon-blue);
            padding: 8px 12px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .control-btn:hover {
            background: var(--neon-blue);
            color: #000;
        }
    </style>
</head>
<body>
<div id="viewer-container">
    <div id="viewer"></div>

    <!-- Панель управления моделью -->
    <div id="controls-overlay">
        <div class="control-group">
            <div class="control-title">ПЕРЕМЕЩЕНИЕ</div>
            <div class="axis-controls">
                <button class="control-btn" data-axis="x" data-direction="+">X+</button>
                <button class="control-btn" data-axis="x" data-direction="-">X-</button>
                <button class="control-btn" data-axis="y" data-direction="+">Y+</button>
                <button class="control-btn" data-axis="y" data-direction="-">Y-</button>
                <button class="control-btn" data-axis="z" data-direction="+">Z+</button>
                <button class="control-btn" data-axis="z" data-direction="-">Z-</button>
            </div>
        </div>

        <div class="control-group">
            <div class="control-title">ВРАЩЕНИЕ</div>
            <div class="axis-controls">
                <button class="control-btn" data-rotate="x">RX</button>
                <button class="control-btn" data-rotate="y">RY</button>
                <button class="control-btn" data-rotate="z">RZ</button>
            </div>
        </div>

        <div class="control-group">
            <div class="control-title">МАСШТАБ</div>
            <div class="axis-controls">
                <button class="control-btn" id="scale-up">+</button>
                <button class="control-btn" id="scale-down">-</button>
                <button class="control-btn" id="reset-scale">Сброс</button>
            </div>
        </div>

        <div class="control-group">
            <div class="control-title">КООРДИНАТЫ</div>
            <div id="position-info" style="font-family: monospace;">
                X: 0.0 | Y: 0.0 | Z: 0.0
            </div>
        </div>
    </div>
</div>

<!-- Three.js и компоненты -->
<script src="https://cdn.jsdelivr.net/npm/three@0.132.2/build/three.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/three@0.132.2/examples/js/controls/OrbitControls.js"></script>
<script src="https://cdn.jsdelivr.net/npm/three@0.132.2/examples/js/loaders/STLLoader.js"></script>

<script>
    // Инициализация сцены
    const scene = new THREE.Scene();
    scene.background = new THREE.Color(0x111126);

    // Камера
    const camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
    camera.position.set(0, 150, 300);

    // Рендерер
    const renderer = new THREE.WebGLRenderer({ antialias: true });
    renderer.setSize(window.innerWidth, window.innerHeight);
    document.getElementById('viewer').appendChild(renderer.domElement);

    // Освещение
    const ambientLight = new THREE.AmbientLight(0x404040);
    scene.add(ambientLight);

    const directionalLight = new THREE.DirectionalLight(0xffffff, 0.8);
    directionalLight.position.set(1, 1, 1);
    scene.add(directionalLight);

    // Платформа
    const platformSize = 220;
    const platformGeometry = new THREE.BoxGeometry(platformSize/10, 2, platformSize/10);
    const platformMaterial = new THREE.MeshPhongMaterial({
        color: 0x333333,
        transparent: true,
        opacity: 0.7
    });
    const platform = new THREE.Mesh(platformGeometry, platformMaterial);
    platform.position.y = -1;
    scene.add(platform);

    // Загрузка модели
    let model = null;
    const loader = new THREE.STLLoader();

    function loadModel(url) {
        loader.load(url, function(geometry) {
            if (model) scene.remove(model);

            geometry.computeBoundingBox();
            const bbox = geometry.boundingBox;
            const center = new THREE.Vector3();
            bbox.getCenter(center);

            const material = new THREE.MeshPhongMaterial({
                color: 0x0abdc6,
                specular: 0x111111,
                shininess: 30
            });

            model = new THREE.Mesh(geometry, material);
            model.position.sub(center);

            const maxDim = Math.max(
                bbox.max.x - bbox.min.x,
                bbox.max.y - bbox.min.y,
                bbox.max.z - bbox.min.z
            );
            const scale = (platformSize/10) * 0.8 / maxDim;
            model.scale.set(scale, scale, scale);
            model.position.y = (bbox.max.y - bbox.min.y) * scale / 2;

            scene.add(model);
            updatePositionInfo();
        });
    }

    // Загружаем модель из URL
    loadModel('/uploads/models/<?= $modelFile ?>');

    // Управление моделью
    const MOVE_STEP = 5;
    const ROTATE_STEP = Math.PI / 8;
    const SCALE_STEP = 0.1;

    document.querySelectorAll('[data-axis]').forEach(btn => {
        btn.addEventListener('click', () => {
            if (!model) return;

            const axis = btn.dataset.axis;
            const dir = btn.dataset.direction === '+' ? 1 : -1;
            model.position[axis] += MOVE_STEP * dir;

            constrainModel();
            updatePositionInfo();
        });
    });

    document.querySelectorAll('[data-rotate]').forEach(btn => {
        btn.addEventListener('click', () => {
            if (!model) return;
            model.rotation[btn.dataset.rotate] += ROTATE_STEP;
            updatePositionInfo();
        });
    });

    document.getElementById('scale-up').addEventListener('click', () => {
        if (!model) return;
        model.scale.multiplyScalar(1 + SCALE_STEP);
        updatePositionInfo();
    });

    document.getElementById('scale-down').addEventListener('click', () => {
        if (!model) return;
        model.scale.multiplyScalar(1 - SCALE_STEP);
        updatePositionInfo();
    });

    document.getElementById('reset-scale').addEventListener('click', () => {
        if (!model) return;
        model.scale.set(1, 1, 1);
        updatePositionInfo();
    });

    function constrainModel() {
        // Ограничение перемещения по платформе
        const halfPlatform = platformSize / 20;
        model.position.x = Math.max(-halfPlatform, Math.min(halfPlatform, model.position.x));
        model.position.z = Math.max(-halfPlatform, Math.min(halfPlatform, model.position.z));
    }

    function updatePositionInfo() {
        if (!model) return;
        document.getElementById('position-info').innerHTML = `
                X: ${model.position.x.toFixed(1)} |
                Y: ${model.position.y.toFixed(1)} |
                Z: ${model.position.z.toFixed(1)}
            `;
    }

    // Анимация
    function animate() {
        requestAnimationFrame(animate);
        renderer.render(scene, camera);
    }

    animate();

    // Реакция на изменение размера
    window.addEventListener('resize', () => {
        camera.aspect = window.innerWidth / window.innerHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(window.innerWidth, window.innerHeight);
    });
</script>
</body>
</html>