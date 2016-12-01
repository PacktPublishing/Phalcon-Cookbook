<?php
namespace VoltCompile\Modules\Cli\Tasks;

class CompileTask extends \Phalcon\Cli\Task
{
    public function mainAction()
    {
        $this->incorrectAction();
    }

    public function incorrectAction()
    {
        $moduleCompiler = new \VoltCompile\ModuleCompiler();
        $moduleCompiler->setDI($this->getDI());

        $moduleCompiler->compile('frontend');

        echo 'Unfortunately there will be issues.';
    }

    public function correctAction()
    {
        $this->fillMissingServices();

        $moduleCompiler = new \VoltCompile\ModuleCompiler();
        $moduleCompiler->setDI($this->getDI());

        $moduleCompiler->compile('frontend');

        echo 'We did it the right way.';
    }

    private function fillMissingServices()
    {
        $config = $this->getDI()
            ->getConfig();

        $diPrimary = $this->getDI();

        $di = new \Phalcon\DI();
        require $config->application->appDir . 'config/services_web.php';

        foreach ($di->getServices() as $serviceName => $service) {

            // We will fill in any missing service that exists only for the web services
            // to ensure that Volt will understand DI services.
            if (!$diPrimary->has($serviceName)) {
                $diPrimary->set($serviceName, function() {});
            }
        }
    }
}
