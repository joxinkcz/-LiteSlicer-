<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3D Printer Lite Slicer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Three.js и дополнительные модули -->
    <script src="https://cdn.jsdelivr.net/npm/three@0.132.2/build/three.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/three@0.132.2/examples/js/controls/OrbitControls.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/three@0.132.2/examples/js/loaders/STLLoader.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/three@0.132.2/examples/js/loaders/OBJLoader.js"></script>

    <!-- Библиотека для слайсинга (используем CuraEngine WASM) -->
    <script src="https://cdn.jsdelivr.net/npm/cura-wasm@1.0.0/dist/cura-wasm.js"></script>

    <!-- Дополнительные утилиты -->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>NEON SLICER 3000</title>

        <!-- Киберпанк шрифты -->
        <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Rajdhani:wght@500;700&display=swap" rel="stylesheet">

        <!-- Стили -->
        <style>
            :root {
                --neon-red: #00ff0d;
                --neon-yellow: #f5d742;
                --neon-blue: #0abdc6;
                --neon-purple: #d300c5;
                --dark-bg: #0c0c1a;
            }

            body {
                background-color: var(--dark-bg);
                color: #e0e0e0;
                font-family: 'Rajdhani', sans-serif;
                overflow-x: hidden;
            }

            .cyber-card {
                background: rgba(12, 12, 26, 0.8);
                border: 1px solid var(--neon-purple);
                border-radius: 0;
                box-shadow: 0 0 15px var(--neon-purple);
                margin-bottom: 25px;
                transition: all 0.3s;
            }

            .cyber-card:hover {
                box-shadow: 0 0 25px var(--neon-blue);
                border-color: var(--neon-blue);
            }

            .cyber-header {
                background: linear-gradient(90deg, var(--neon-red), var(--neon-purple));
                color: black;
                font-family: 'Orbitron', sans-serif;
                font-weight: 700;
                letter-spacing: 2px;
                border-bottom: 2px solid var(--neon-yellow);
            }

            .cyber-btn {
                background: transparent;
                color: var(--neon-yellow);
                border: 1px solid var(--neon-yellow);
                font-family: 'Orbitron', sans-serif;
                letter-spacing: 1px;
                transition: all 0.3s;
                position: relative;
                overflow: hidden;
            }
            #viewer-container {
                position: relative;
                width: 100%;
                height: 600px;
                background: #111;
                border: 1px solid #0abdc6;
                overflow: hidden;
            }

            #viewer {
                width: 100%;
                height: 100%;
            }

            .control-panel {
                position: absolute;
                bottom: 20px;
                right: 20px;
                z-index: 100;
                display: flex;
                gap: 10px;
            }

            .control-btn {
                background: rgba(0, 0, 0, 0.7);
                border: 1px solid #0abdc6;
                color: #0abdc6;
                width: 40px;
                height: 40px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                font-size: 20px;
            }

            .control-btn:hover {
                background: #0abdc6;
                color: #000;
            }
            .cyber-btn:hover {
                color: black;
                background: var(--neon-yellow);
                box-shadow: 0 0 15px var(--neon-yellow);
            }

            /* Эффекты неона */
            .neon-effect {
                text-shadow: 0 0 5px currentColor;
            }

            /* Глитч эффекты */
            @keyframes glitch {
                0% { transform: translate(0); }
                20% { transform: translate(-2px, 2px); }
                40% { transform: translate(-2px, -2px); }
                60% { transform: translate(2px, 2px); }
                80% { transform: translate(2px, -2px); }
                100% { transform: translate(0); }
            }

            .glitch-effect:hover {
                animation: glitch 0.5s linear infinite;
            }

    <!-- Хакерская матрица фона -->
    <div id="matrix-effect" style="position:fixed;top:0;left:0;z-index:-1;opacity:0.1"></div>

         #viewer-container {
             position: relative;
             width: 100%;
             height: 600px;
             background-color: #f0f0f0;
             border-radius: 5px;
             overflow: hidden;
         }

        #viewer {
            width: 100%;
            height: 100%;
        }

        .form-range::-webkit-slider-thumb {
            background: #0d6efd;
        }

        .btn-zoom {
            margin-right: 5px;
        }

        body { padding-top: 20px; }
        .modal-backdrop { background-color: rgba(0,0,0,0.5); }

    </style>
    <style>
        #viewer-container {
            position: relative;
            width: 100%;
            height: 600px;
            border: 1px solid #ddd;
        }

        .form-range::-webkit-slider-thumb {
            background: #0d6efd;
        }

        #gcode-preview {
            height: 200px;
            overflow-y: auto;
            background: #f8f9fa;
            font-family: monospace;
            white-space: pre;
            padding: 10px;
        }
    </style>
</head>
<body>