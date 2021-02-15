<?php

namespace RUB\InstanceIndicatorExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

/**
 * ExternalModule class for Instance-Type Indicator.
 * 
 */
class InstanceIndicatorExternalModule extends AbstractExternalModule {


    private $style = array();

    function redcap_every_page_top($project_id = null) {
        $this->style = $this->_getStyle();
        $this->injectIndicator();
    }

    /**
     * Build and inject the indicator (a div with an svg inside; some JS if navbar should be 
     * excluded when the indicator is shown at the top of the page).
     * 
     */
    function injectIndicator() {
        // Only do the work if the indicator is not 'disabled'.
        if ($this->style["type"] == "disabled") return;
        // Limited display?
        if ($this->style["displayfor"] == "superusers" && (SUPER_USER != "1")) return;
        if ($this->style["displayfor"] == "selected" && !in_array(USERID, $this->style["displayfor_users"])) return;
        
        // Calculate some values up front.
        $size = 100 * $this->style["scale"];
        $size2 = $size / 2;
        $textXY = 50 * $this->style["scale"];
        $translateY = 16 * $this->style["scale"];
        $fontsize = $this->style["fontsize"] * $this->style["scale"];
        // Some values depend on where the indicator should be shown.
        switch ($this->style["position"]) {
            case "tl":
                $posX = "left";
                $posY = "top";
                $rotate = "270";
                $poly = ($this->style["style"] == "ribbon") ? 
                    "0,0 {$size},{$size} {$size},{$size2} {$size2},0" : 
                    "0,0 {$size},{$size} {$size},0";
                $translate = "0,-{$translateY}";
                break;
            case "tr":
                $posX = "right";
                $posY = "top";
                $rotate = "0";
                $poly = ($this->style["style"] == "ribbon") ? 
                    "0,0 {$size},{$size} {$size},{$size2} {$size2},0" : 
                    "0,0 {$size},{$size} {$size},0";
                $translate = "0,-{$translateY}";
                break;
            case "bl":
                $posX = "left";
                $posY = "bottom";
                $rotate = "0";
                $poly = ($this->style["style"] == "ribbon") ? 
                    "0,0 {$size},{$size} {$size2},{$size} 0,{$size2}" : 
                    "0,0 {$size},{$size} 0,{$size}";
                $translate = "0,{$translateY}";
                break;
            case "br":
                $posX = "right";
                $posY = "bottom";
                $rotate = "270";
                $poly = ($this->style["style"] == "ribbon") ? 
                    "0,0 {$size},{$size} {$size2},{$size} 0,{$size2}" : 
                    "0,0 {$size},{$size} 0,{$size}";
                $translate = "0,{$translateY}";
                break;
                
        }
        // Show under/over navbar?
        $navbar = $posY == "top" && $this->style["navbar"] == "below";
        $display = $navbar ? "display:none;" : "";
        $class = $this->style["printable"] ? "" : "d-print-none";
        // Using the calculated values, output the DIV with the SVG.
        echo "<div id=\"redcap-instance-indicator\" class=\"{$class}\" 
                style=\"{$display}position:fixed;{$posX}:0;{$posY}:0;opacity:{$this->style["opacity"]};z-index:9999;pointer-events:none\">
                <svg width=\"{$size}\" height=\"{$size}\" transform=\"rotate({$rotate} )\">
                    <polygon points=\"{$poly}\" fill=\"{$this->style["bgcolor"]}\" style=\"opacity:{$this->style["opacity"]}\" />
                    <text text-anchor=\"middle\" dominant-baseline=\"middle\" x=\"{$textXY}\" y=\"{$textXY}\" 
                        fill=\"{$this->style["color"]}\" transform=\"rotate(45,{$textXY},{$textXY}) translate({$translate})\" 
                        style=\"font-size: {$fontsize}px; font-family: Open Sans, Helvetica, Arial, sans-serif; font-weight: bold\">
                        {$this->style["text"]}
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

    /**
     * This helper function assembles the style values of the indicator based on 
     * the settings made in the External Module configuration.
     * @return array The style settings.
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
        $style["printable"] = $this->_getValue($values, "printable", false);
        $style["opacity"] = $this->_getValue($values, "opacity", 0.8, true);
        $style["scale"] = $this->_getValue($values, "scale", 1.0, true);
        $style["displayfor"] = $this->_getValue($values, "displayfor", "everyone");
        $style["displayfor_users"] = $this->_getUsers($this->_getValue($values, "displayfor_users", ""));
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
     * @return mixed The value.
     */
    function _getValue($values, $name, $default, $numeric = false) {
        $value = $values["indicator_{$name}"]["system_value"];
        if (is_array($value)) $value = $value[0];
        if ($value == null) return $default;
        if ($numeric && !is_numeric($value)) return $default;
        return $value;
    }

    /**
     * This helper functions splits a user list (separated by newlines) into an array.
     * @param $raw A newline-separated list of users ids.
     * @return array Array of user ids.
     */
    function _getUsers($raw) {
        $list = array();
        $rawList = explode("\n", $raw);
        foreach ($rawList as $user) {
            $user = trim($user);
            if (strlen($user)) array_push($list, $user);
        }
        return $list;
    }
}