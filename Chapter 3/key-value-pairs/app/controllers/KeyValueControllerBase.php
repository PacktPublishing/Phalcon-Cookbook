<?php

class KeyValueControllerBase extends \Phalcon\Mvc\Controller
{

    public function beforeExecuteRoute($dispatcher)
    {
        $keyParams = [];
        $params = $dispatcher->getParams();
        foreach ($params as $number => $value) {
            if ($number & 1) {
                $keyParams[$params[$number - 1]] = $value;
            }
        }
        $dispatcher->setParams($keyParams);
    }
}
