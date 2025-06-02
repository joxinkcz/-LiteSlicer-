<?php
require_once __DIR__.'/Config.php';

class SlicerProfile {
    private $printerName;

    public function __construct($printerName = 'Anycubic Kobra 3') {
        if (!isset(PRINTER_PROFILES[$printerName])) {
            throw new Exception("Printer profile not found");
        }

        $this->printerName = $printerName;
    }

    public function getStartGcode($material) {
        $profile = PRINTER_PROFILES[$this->printerName];
        $materialSettings = MATERIALS[$material];

        $gcode = [];
        $gcode[] = "; Start G-code for {$this->printerName}";
        $gcode[] = "M140 S{$materialSettings['bed_temp'][1]} ; Set bed temp";
        $gcode[] = "M104 S{$materialSettings['extruder_temp'][1]} ; Set extruder temp";
        $gcode[] = "G28 ; Home all axes";
        $gcode[] = "G29 ; Auto bed leveling";
        $gcode[] = "M190 S{$materialSettings['bed_temp'][1]} ; Wait for bed temp";
        $gcode[] = "M109 S{$materialSettings['extruder_temp'][1]} ; Wait for extruder temp";
        $gcode[] = "G21 ; Set units to millimeters";
        $gcode[] = "G90 ; Use absolute positioning";
        $gcode[] = "M82 ; Set extruder to absolute mode";

        return implode("\n", $gcode);
    }

    public function getEndGcode() {
        $gcode = [];
        $gcode[] = "; End G-code for {$this->printerName}";
        $gcode[] = "G91 ; Relative positioning";
        $gcode[] = "G1 Z10 F900 ; Lift nozzle";
        $gcode[] = "G90 ; Absolute positioning";
        $gcode[] = "G1 X0 Y220 F9000 ; Present print";
        $gcode[] = "M104 S0 ; Turn off extruder";
        $gcode[] = "M140 S0 ; Turn off bed";
        $gcode[] = "M107 ; Turn off fan";
        $gcode[] = "M84 ; Disable steppers";

        return implode("\n", $gcode);
    }

    public function getNozzleSizes() {
        return PRINTER_PROFILES[$this->printerName]['nozzle_sizes'];
    }
}