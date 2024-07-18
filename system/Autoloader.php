<?php

namespace AnserGateway;

use Config\Autoloader as AutoloaderConfig;

class Autoloader
{
    protected $prefixes = array();

    protected AutoloaderConfig $autoloadConfig;

    public static Autoloader $instance;

    public function __construct(AutoloaderConfig $autoloadConfig)
    {
        $this->autoloadConfig = $autoloadConfig;
        static::$instance = $this;
    }

    public function appRegister()
    {
        foreach ($this->autoloadConfig->appNamespaces as $namespace => $dir) {
            $this->addNamespace($namespace, $dir);
        }
        spl_autoload_register(array($this, 'loadClass'));
    }


    public function systemRegister()
    {
        foreach ($this->autoloadConfig->systemNamespaces as $namespace => $dir) {
            $this->addNamespace($namespace, $dir);
        }
        spl_autoload_register(array($this, 'loadClass'));
    }

    public function composerRegister()
    {
        require_once PROJECT_ROOT . 'vendor' . DIRECTORY_SEPARATOR . '/autoload.php';
    }

    public function addNamespace($prefix, $base_dir, $prepend = false)
    {
        $prefix = trim($prefix, '\\') . '\\';

        $base_dir = rtrim($base_dir, DIRECTORY_SEPARATOR) . '/';

        if (isset($this->prefixes[$prefix]) === false) {
            $this->prefixes[$prefix] = array();
        }

        if ($prepend) {
            array_unshift($this->prefixes[$prefix], $base_dir);
        } else {
            array_push($this->prefixes[$prefix], $base_dir);
        }
    }

    public function loadClass($class)
    {
        $prefix = $class;

        while (false !== $pos = strrpos($prefix, '\\')) {

            $prefix = substr($class, 0, $pos + 1);

            $relative_class = substr($class, $pos + 1);

            $mapped_file = $this->loadMappedFile($prefix, $relative_class);
            if ($mapped_file) {
                return $mapped_file;
            }

            $prefix = rtrim($prefix, '\\');
        }

        return false;
    }

    protected function loadMappedFile($prefix, $relative_class)
    {
        if (isset($this->prefixes[$prefix]) === false) {
            return false;
        }

        foreach ($this->prefixes[$prefix] as $base_dir) {

            $file = $base_dir
                  . str_replace('\\', '/', $relative_class)
                  . '.php';

            if ($this->requireFile($file)) {
                return $file;
            }
        }

        return false;
    }

    protected function requireFile($file)
    {
        if (file_exists($file)) {
            require_once $file;
            return true;
        }
        return false;
    }
}
