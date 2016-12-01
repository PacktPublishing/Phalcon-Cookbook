<?php

class Clients extends Phalcon\Mvc\Model
{
    private static $selected;

    public static function getSelected()
    {
        if (!isset(self::$selected)) {
            throw new Exception('A client has not yet been selected.');
        }
        return self::$selected;
    }

    public function select()
    {
        if (isset(self::$selected)) {
            throw new Exception('The client may only be selected once.');
        }
        self::$selected = $this;
    }

    public function initialize()
    {
        $this->setConnectionService('db');
    }
}
