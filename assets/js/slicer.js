// Основные переменные
let scene, camera, renderer, controls, model;
let isDragging = false;
let selectedAxis = null;
let previousMousePosition = { x: 0, y: 0 };
let dragStartPosition = new THREE.Vector3();
const platformSize = 220; // Размер платформы в мм

// Инициализация сцены
function initScene() {
    // Создаем сцену
    scene = new THREE.Scene();
    scene.background = new THREE.Color(0x111126);

    // Создаем камеру
    const container = document.getElementById('viewer-container');
    camera = new THREE.PerspectiveCamera(
        75,
        container.clientWidth / container.clientHeight,
        0.1,
        1000
    );
    camera.position.set(0, 150, 300);

    // Создаем рендерер
    renderer = new THREE.WebGLRenderer({
        antialias: true,
        alpha: true // Прозрачный фон для отладки
    });
    renderer.setSize(container.clientWidth, container.clientHeight);
    renderer.shadowMap.enabled = true;
    document.getElementById('viewer').appendChild(renderer.domElement);

    // Освещение
    const ambientLight = new THREE.AmbientLight(0x404040);
    scene.add(ambientLight);

    const directionalLight = new THREE.DirectionalLight(0xffffff, 1.0);
    directionalLight.position.set(1, 1, 1);
    directionalLight.castShadow = true;
    scene.add(directionalLight);

    // Добавляем OrbitControls
    controls = new THREE.OrbitControls(camera, renderer.domElement);
    controls.enableDamping = true;
    controls.dampingFactor = 0.05;
    controls.screenSpacePanning = false;
    controls.maxPolarAngle = Math.PI;
    controls.minPolarAngle = 0;

    // Создаем платформу
    createPlatform();

    // Добавляем оси для отладки
    const axesHelper = new THREE.AxesHelper(50);
    scene.add(axesHelper);

    // Загрузка модели из URL
    const urlParams = new URLSearchParams(window.location.search);
    const modelFile = urlParams.get('model');
    if (modelFile) {
        console.log('Загрузка модели:', modelFile);
        loadModel(`/uploads/models/${modelFile}`);
    }

    // Обработчики событий
    setupEventListeners();

    // Анимация
    animate();
}

// Создание платформы
function createPlatform() {
    const platformGeometry = new THREE.BoxGeometry(
        platformSize/10,
        2,
        platformSize/10
    );
    const platformMaterial = new THREE.MeshPhongMaterial({
        color: 0x333333,
        transparent: true,
        opacity: 0.7
    });
    const platform = new THREE.Mesh(platformGeometry, platformMaterial);
    platform.position.y = -1;
    platform.receiveShadow = true;
    scene.add(platform);

    // Сетка платформы
    const gridHelper = new THREE.GridHelper(
        platformSize/5,
        20,
        0x555555,
        0x333333
    );
    gridHelper.position.y = 0;
    scene.add(gridHelper);

    // Границы платформы
    const edges = new THREE.EdgesGeometry(platformGeometry);
    const lineMaterial = new THREE.LineBasicMaterial({
        color: 0x00ff00,
        linewidth: 2
    });
    const line = new THREE.LineSegments(edges, lineMaterial);
    line.position.copy(platform.position);
    scene.add(line);
}

// Загрузка модели с центрированием
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
            flatShading: true,
            transparent: true,
            opacity: 0.9
        });

        model = new THREE.Mesh(geometry, material);
        model.castShadow = true;

        // Center and scale model
        geometry.computeBoundingBox();
        const boundingBox = geometry.boundingBox;
        const modelCenter = new THREE.Vector3();
        boundingBox.getCenter(modelCenter);
        model.position.sub(modelCenter);

        const size = boundingBox.getSize(new THREE.Vector3());
        const maxDim = Math.max(size.x, size.y, size.z);
        const scale = 5 / maxDim;
        model.scale.set(scale, scale, scale);

        // Сохраняем оригинальный масштаб для сброса
        model.userData.originalScale = scale;
        model.userData.originalSize = size.clone();

        scene.add(model);

        // Update model info
        updateModelInfo(size, scale);

        // Adjust camera to fit model
        fitCameraToModel(boundingBox, scale);

        // Добавляем оси для отладки
        scene.add(new THREE.AxesHelper(10));

        console.log('Модель успешно загружена:', {
            position: model.position,
            scale: model.scale,
            size: size
        });

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

// Функция масштабирования модели
function scaleModel(factor) {
    if (!model) return;

    model.scale.multiplyScalar(factor);
    updateModelInfo(model.userData.originalSize, model.scale.x);
    console.log('Масштаб изменен:', model.scale);
}

// Сброс масштаба
function resetModelScale() {
    if (!model || !model.userData.originalScale) return;

    model.scale.setScalar(model.userData.originalScale);
    updateModelInfo(model.userData.originalSize, model.scale.x);
    console.log('Масштаб сброшен:', model.scale);
}

// Обновление информации о модели
function updateModelInfo(size, scale) {
    document.getElementById('model-dimensions').textContent =
        `${(size.x * scale).toFixed(1)} × ${(size.y * scale).toFixed(1)} × ${(size.z * scale).toFixed(1)} mm`;

    const volume = (size.x * size.y * size.z * scale * scale * scale / 1000).toFixed(1);
    document.getElementById('model-volume').textContent = `${volume} cm³`;
}

// Настройка камеры под модель
function fitCameraToModel(boundingBox, scale) {
    const size = boundingBox.getSize(new THREE.Vector3());
    const center = boundingBox.getCenter(new THREE.Vector3());
    const maxDim = Math.max(size.x, size.y, size.z);

    let cameraZ = Math.abs(maxDim * scale / Math.tan(Math.PI * camera.fov / 360)) * 1.1;
    camera.position.set(0, size.y * scale * 0.5, cameraZ);
    controls.target.copy(center);
    controls.update();

    console.log('Камера настроена:', {
        position: camera.position,
        target: controls.target
    });
}

// Обновление информации о модели
function updateModelInfo(size, scale) {
    document.getElementById('model-dimensions').textContent =
        `${(size.x * scale).toFixed(1)} × ${(size.y * scale).toFixed(1)} × ${(size.z * scale).toFixed(1)} мм`;

    const volume = (size.x * size.y * size.z * scale * scale * scale / 1000).toFixed(1);
    document.getElementById('model-volume').textContent = `${volume} см³`;
}

// Настройка обработчиков событий
function setupEventListeners() {
    const rendererDom = renderer.domElement;

    // Двойной клик для перемещения
    rendererDom.addEventListener('dblclick', onDoubleClick);

    // Перемещение модели
    rendererDom.addEventListener('mousedown', onMouseDown);
    rendererDom.addEventListener('mousemove', onMouseMove);
    rendererDom.addEventListener('mouseup', onMouseUp);
    rendererDom.addEventListener('contextmenu', onRightClick);

    // Обработка изменения размера окна
    window.addEventListener('resize', onWindowResize);
}

// Обработчик изменения размера окна
function onWindowResize() {
    const container = document.getElementById('viewer-container');
    camera.aspect = container.clientWidth / container.clientHeight;
    camera.updateProjectionMatrix();
    renderer.setSize(container.clientWidth, container.clientHeight);
}

// Обработчики событий мыши
function onDoubleClick(event) {
    if (!model) return;

    const mouse = getNormalizedMousePosition(event);
    const raycaster = new THREE.Raycaster();
    raycaster.setFromCamera(mouse, camera);
    const intersects = raycaster.intersectObject(model);

    if (intersects.length > 0) {
        isDragging = true;
        dragStartPosition.copy(model.position);
        previousMousePosition = { x: event.clientX, y: event.clientY };
        showMovementAxis();
    }
}

function onMouseMove(event) {
    if (!isDragging || !model) return;

    const deltaX = event.clientX - previousMousePosition.x;
    const deltaY = event.clientY - previousMousePosition.y;
    const sensitivity = 0.05;

    if (selectedAxis === 'x') {
        model.position.x = dragStartPosition.x + deltaX * sensitivity;
    } else if (selectedAxis === 'y') {
        model.position.y = dragStartPosition.y - deltaY * sensitivity;
    } else if (selectedAxis === 'z') {
        model.position.z = dragStartPosition.z + deltaX * sensitivity;
    }

    constrainModelToPlatform();
}

function onMouseUp() {
    isDragging = false;
}

function onRightClick(event) {
    if (!model) return;

    event.preventDefault();
    const mouse = getNormalizedMousePosition(event);
    const raycaster = new THREE.Raycaster();
    raycaster.setFromCamera(mouse, camera);
    const intersects = raycaster.intersectObject(model);

    if (intersects.length > 0) {
        showContextMenu(event.clientX, event.clientY);
    }
}

// Получение нормализованных координат мыши
function getNormalizedMousePosition(event) {
    const rect = renderer.domElement.getBoundingClientRect();
    return {
        x: ((event.clientX - rect.left) / rect.width) * 2 - 1,
        y: -((event.clientY - rect.top) / rect.height) * 2 + 1
    };
}

// Контекстное меню
function showContextMenu(x, y) {
    const menu = document.getElementById('context-menu');
    menu.style.display = 'block';
    menu.style.left = `${x}px`;
    menu.style.top = `${y}px`;
}

// Показать оси перемещения
function showMovementAxis() {
    // Реализация показа осей перемещения
}

// Ограничение модели платформой
function constrainModelToPlatform() {
    if (!model) return;

    const size = new THREE.Vector3();
    model.geometry.computeBoundingBox();
    model.geometry.boundingBox.getSize(size);
    size.multiply(model.scale);

    const halfPlatform = platformSize / 20;
    const halfWidth = size.x / 2;
    const halfDepth = size.z / 2;

    model.position.x = Math.max(-halfPlatform + halfWidth,
        Math.min(halfPlatform - halfWidth, model.position.x));
    model.position.z = Math.max(-halfPlatform + halfDepth,
        Math.min(halfPlatform - halfDepth, model.position.z));
    model.position.y = Math.max(size.y / 2, model.position.y);
}

// Анимация
function animate() {
    requestAnimationFrame(animate);
    controls.update();
    renderer.render(scene, camera);
}

// Инициализация при загрузке
document.addEventListener('DOMContentLoaded', initScene);