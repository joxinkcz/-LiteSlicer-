<?php
require_once __DIR__.'/Config.php';

class GcodeGenerator {
    private $settings;
    private $modelPath;

    public function __construct($settings, $modelPath) {
        $this->settings = $settings;
        $this->modelPath = $modelPath;
    }

    public function generate() {
        // Получаем настройки материала и принтера
        $material = MATERIALS[$this->settings['material']];
        $printer = PRINTER_PROFILES['Anycubic Kobra 3'];

        // Генерация G-code с помощью Prusa Slicer
        $outputFile = 'slice_'.time().'_'.bin2hex(random_bytes(4)).'.gcode';
        $gcodePath = GCODE_UPLOAD_DIR . $outputFile;

        $command = sprintf(
            '"%s" --load "%s" --export-gcode --output "%s" "%s" '.
            '--layer-height %s '.
            '--fill-density %d '.
            '--nozzle-diameter %s '.
            '%s '.
            '%s',
            PRUSA_SLICER_PATH,
            PRINTER_PROFILE_PATH,
            escapeshellarg($gcodePath),
            escapeshellarg($this->modelPath),
            $this->settings['layer_height'],
            $this->settings['infill_density'],
            $this->settings['nozzle_size'],
            ($this->settings['support'] === 'true' ? '--support-material' : ''),
            ($this->settings['brim'] === 'brim' ? '--brim-width 5' : '')
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0 || !file_exists($gcodePath)) {
            throw new Exception("Slicing failed: ".implode("\n", $output));
        }

        return file_get_contents($gcodePath);
    }

    private function simulateSlicing() {
        // В реальном проекте замените на вызов CuraEngine или другой библиотеки
        $simulatedGcode = "";

        // Симуляция слоев
        $layers = 100 * (0.2 / $this->settings['layer_height']); // Примерное количество слоев

        for ($i = 0; $i < $layers; $i++) {
            $z = $this->settings['layer_height'] * $i;
            $simulatedGcode .= "; LAYER:$i\n";
            $simulatedGcode .= "G1 Z{$z} F3000 ; Move to layer height\n";

            // Симуляция периметров
            for ($p = 0; $p < 2; $p++) {
                $simulatedGcode .= "G1 X10 Y10 F9000\n";
                $simulatedGcode .= "G1 X190 Y10 F9000\n";
                $simulatedGcode .= "G1 X190 Y190 F9000\n";
                $simulatedGcode .= "G1 X10 Y190 F9000\n";
                $simulatedGcode .= "G1 X10 Y10 F9000\n";
            }

            // Симуляция заполнения
            if ($this->settings['infill_density'] > 0) {
                $simulatedGcode .= "; INFILL\n";
                $lines = ceil($this->settings['infill_density'] / 10);

                for ($l = 0; $l < $lines; $l++) {
                    $y = 10 + ($l * (180 / $lines));
                    $simulatedGcode .= "G1 X10 Y{$y} F9000\n";
                    $simulatedGcode .= "G1 X190 Y{$y} F9000\n";
                }
            }
        }

        return $simulatedGcode;
    }

    public function estimatePrintTime() {
        // Простая оценка времени печати
        $volume = $this->estimateModelVolume();
        $speed = 50; // мм/с
        $time = ($volume * 0.1) / ($speed * $this->settings['layer_height']);

        return round(max($time, 10)); // Минимум 10 минут
    }

    public function estimateFilamentUsed() {
        // Простая оценка расхода филамента
        $volume = $this->estimateModelVolume();
        $density = 1.25; // г/см³ (PLA)
        $filament = ($volume * $density) * ($this->settings['infill_density'] / 100);

        return round($filament, 1);
    }

    private function estimateModelVolume() {
        // В реальном проекте используйте анализ модели
        // Здесь упрощенная оценка
        $filesize = filesize($this->modelPath);
        return $filesize / 100000; // Примерная оценка объема в см³
    }
}
?>