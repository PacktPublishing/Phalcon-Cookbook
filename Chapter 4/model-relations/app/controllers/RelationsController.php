<?php

class RelationsController extends Phalcon\Mvc\Controller
{
    public function indexAction()
    {
        $this->response->appendContent('<h3>Hats</h3>');
        foreach(Hats::find() as $hat) {
            $this->printHatColors($hat);
        }

        $this->response->appendContent('<h3>Colors</h3>');
        foreach(Colors::find() as $color) {
            $this->printColorHats($color);
        }

        $this->response->send();
    }

    private function printHatColors($hat)
    {
        $colors = $hat->getRelated('colors');

        $this->response->appendContent($hat->name . ':');
        if ($colors->count() > 0) {
            foreach ($colors as $color) {
                $this->response->appendContent(' ' . $color->name);
            }

        } else {
            $this->response->appendContent(' without color');
        }
        $this->response->appendContent('<br>');
    }

    private function printColorHats($color)
    {
        $hats = $color->getHats();

        $this->response->appendContent($color->name . ':');
        if ($hats->count() > 0) {
            foreach ($hats as $hat) {
                $this->response->appendContent(' ' . $hat->name);
            }

        } else {
            $this->response->appendContent(' without hats');
        }
        $this->response->appendContent('<br>');
    }
}
