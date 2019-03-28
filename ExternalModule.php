<?php

namespace RUB\InstanceTypeIndicator\ExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

/**
 * ExternalModule class for Instance-Type Indicator.
 * 
 */
class ExternalModule extends AbstractExternalModule {

    function redcap_every_page_top($project_id = null) {
        $this->injectIndicator();
    }

    /**
     * Build and inject the indicator (a div with an svg inside; some JS if navbar should be 
     * excluded when the indicator is shown at the top of the page).
     * 
     */
    function injectIndicator() {
        $style = $this->_getStyle();
        // Only do the work if the indicator is not 'disabled'.
        if ($style["type"] !== "disabled") {
            // Calculate some values up front.
            $size = 100 * $style["scale"];
            $size2 = $size / 2;
            $textXY = 50 * $style["scale"];
            $translateY = 16 * $style["scale"];
            $fontsize = $style["fontsize"] * $style["scale"];
            // Some values depend on where the indicator should be shown.
            switch ($style["position"]) {
                case "tl":
                    $posX = "left";
                    $posY = "top";
                    $rotate = "270";
                    $poly = ($style["style"] == ribbon) ? 
                        "0,0 {$size},{$size} {$size},{$size2} {$size2},0" : 
                        "0,0 {$size},{$size} {$size},0";
                    $translate = "0,-{$translateY}";
                    break;
                case "tr":
                    $posX = "right";
                    $posY = "top";
                    $rotate = "0";
                    $poly = ($style["style"] == ribbon) ? 
                        "0,0 {$size},{$size} {$size},{$size2} {$size2},0" : 
                        "0,0 {$size},{$size} {$size},0";
                    $translate = "0,-{$translateY}";
                    break;
                case "bl":
                    $posX = "left";
                    $posY = "bottom";
                    $rotate = "0";
                    $poly = ($style["style"] == ribbon) ? 
                        "0,0 {$size},{$size} {$size2},{$size} 0,{$size2}" : 
                        "0,0 {$size},{$size} 0,{$size}";
                    $translate = "0,{$translateY}";
                    break;
                case "br":
                    $posX = "right";
                    $posY = "bottom";
                    $rotate = "270";
                    $poly = ($style["style"] == ribbon) ? 
                        "0,0 {$size},{$size} {$size2},{$size} 0,{$size2}" : 
                        "0,0 {$size},{$size} 0,{$size}";
                    $translate = "0,{$translateY}";
                    break;
                    
            }
            // Show under/over navbar?
            $navbar = $posY == "top" && $style["navbar"] == "below";
            $display = $navbar ? "display:none;" : "";
            // Using the calculated values, output the DIV with the SVG.
            echo "<div id=\"redcap-instance-indicator\" 
                    style=\"{$display}position:fixed;{$posX}:0;{$posY}:0;opacity:{$style["opacity"]};z-index:9999;pointer-events:none\">
                    <svg width=\"{$size}\" height=\"{$size}\" transform=\"rotate({$rotate})\">
                        <polygon points=\"{$poly}\" fill=\"{$style["bgcolor"]}\" style=\"opacity:{$style["opacity"]}\" />
                        <text text-anchor=\"middle\" dominant-baseline=\"middle\" x=\"{$textXY}\" y=\"{$textXY}\" 
                            fill=\"{$style["color"]}\" transform=\"rotate(45,{$textXY},{$textXY}) translate({$translate})\" 
                            style=\"font-size: {$fontsize}px; font-family: Open Sans, Helvetica, Arial, sans-serif; font-weight: bold\">
                            {$style["text"]}
                        </text>
                    </svg>
                </div>";
            // If the navbar should be excluded, we need some additional JavaScript to adjust the position of the indicator.
            if ($navbar) echo '<script>
                $(function() {
                    const h = document.getElementsByClassName("navbar")[0].offsetHeight
                    const e = document.getElementById("redcap-instance-indicator")
                    e.style.top = h + "px"
                    e.style.display = "block"
                });
            </script>';
        }
    }

    /**
     * This helper function assembles the style values of the indicator based on 
     * the settings made in the External Module configuration.
     * 
     */
    function _getStyle() {
        $values = ExternalModules::getSystemSettingsAsArray($this->PREFIX);
        // Define some defaults.
        $style = array(
            "text" => "DEVELOPMENT",
            "color" => "white",
            "bgcolor" => "red",
            "fontsize" => 12
        );
        // And further populate/change based on the configuration values.
        $style["type"] = $this->_getValue($values, "type", "disabled");
        if ($style["type"] == "staging") {
            $style["text"] = "STAGING";
            $style["bgcolor"] = "blue";
        }
        else if ($style["type"] == "custom") {
            $style["text"] = $this->_getValue($values, "text", "CUSTOM");
            $style["fontsize"] = $this->_getValue($values, "fontsize", 12, true);
            $style["color"] = $this->_getValue($values, "color", "white");
            $style["bgcolor"] = $this->_getValue($values, "bgcolor", "gray");
        }
        $style["position"] = $this->_getValue($values, "position", "tl");
        $style["navbar"] = $this->_getValue($values, "navbar", "over");
        $style["style"] = $this->_getValue($values, "style", "ribbon");
        $style["opacity"] = $this->_getValue($values, "opacity", 0.8, true);
        $style["scale"] = $this->_getValue($values, "scale", 1.0, true);
        return $style;
    }

    /**
     * This helper function pulls out values from settings values
     * 
     * @param array $values
     *   The External Module configuration values.
     * @param string $name 
     *   The name of the settings value (without the 'indicator_' prefix).
     * @param mixed $default 
     *   The default value to return when there is no value configured (null).
     * @param bool $numeric
     *   Indicates whether the configuration value must be numeric. If it's not, 
     *   the default is returned instead.
     * 
     */
    function _getValue($values, $name, $default, $numeric = false) {
        $value = $values["indicator_{$name}"]["system_value"];
        if (is_array($value)) $value = $value[0];
        if ($value == null) return $default;
        if ($numeric && !is_numeric($value)) return $default;
        return $value;
    }
}