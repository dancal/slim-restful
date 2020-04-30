<?php

namespace SlimRestful;

class SettingsManager {

    /**
     * @var SettingsManager|null $instance
     */
    protected static ?SettingsManager $instance = null;
    /**
     * @var array $settings
     */
    protected array $settings;

    /**
     * __construct
     */
    private function __construct() {
        $this->settings = array();
    }

    /**
     * SINGLETON
     * 
     * @return SettingsManager
     */
    public static function getInstance(): SettingsManager {
 
        if(is_null(self::$instance)) {
          self::$instance = new SettingsManager();
        }
    
        return self::$instance;
    }

    /**
     * Get a setting value
     * 
     * @param string $name setting name
     * 
     * @return mixed|null
     */
    public function get(string $name) {
        return array_key_exists($name, $this->settings) ? $this->settings[$name] : null;
    }

    /**
     * Add settings to the SettingsManager
     * 
     * @param array settings array
     * 
     * @return void
     */
    public function addSettings(array $settings): void {
        $this->settings = array_merge($settings, $this->settings);
    }
}