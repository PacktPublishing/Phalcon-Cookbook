<?php
namespace VoltCompile;

class ModuleCompiler extends \Phalcon\DI\Injectable
{
    public function __construct()
    {
        if (php_sapi_name() !== "cli") {
            throw new \Exception('The module compiler must be run from the command line.');
        }
    }

    public function compile($moduleName)
    {
        $moduleClass = '\\' . __NAMESPACE__ . '\\Modules\\' . ucfirst($moduleName) . '\\Module';
        $module = new $moduleClass();

        // Create a temporary DI and register the module services to it.
        $diModule = new \Phalcon\DI();
        $module->registerServices($diModule);

        // Get an raw unresolved view function and bind it to our DI instead of our temporary DI.
        $viewFactory = \Closure::bind($diModule->getRaw('view'), $this->getDI());

        $this->compileVoltDir($viewFactory()->getViewsDir(), $viewFactory);
    }

    private function compileVoltDir($path, $viewFactory)
    {
        $dh = opendir($path);
        while (($fileName = readdir($dh)) !== false) {
            if ($fileName == '.' || $fileName == '..') {
                continue;
            }

            $pathNext = $path . $fileName;
            if (is_dir($pathNext)) {
                $this->compileVoltDir("$pathNext/", $viewFactory);
            } else {
                $this->getDI()
                    ->getVoltShared($viewFactory())
                    ->getCompiler()
                    ->compile($pathNext);
            }
        }

        // close the directory handle
        closedir($dh);
    }
}
