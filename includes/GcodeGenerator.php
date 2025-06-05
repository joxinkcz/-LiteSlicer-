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
        $material = MATERIALS[$this->settings['material']];
        $printer = PRINTER_PROFILES['Anycubic Kobra 3'];

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

        return [
            'filename' => $outputFile,
            'content' => file_get_contents($gcodePath),
            'path' => $gcodePath
        ];
    }

    public function estimatePrintTime() {
        $volume = $this->estimateModelVolume();
        $speed = 50; // mm/s
        $time = ($volume * 0.1) / ($speed * $this->settings['layer_height']);
        return round(max($time, 10)); // Minimum 10 minutes
    }

    public function estimateFilamentUsed() {
        $volume = $this->estimateModelVolume();
        $density = 1.25; // g/cm³ (PLA)
        $filament = ($volume * $density) * ($this->settings['infill_density'] / 100);
        return round($filament, 1);
    }

    private function estimateModelVolume() {
        $filesize = filesize($this->modelPath);
        return $filesize / 100000; // Simplified volume estimation
    }
}