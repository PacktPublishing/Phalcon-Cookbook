<?php

class BuilderController extends Phalcon\Mvc\Controller
{
    public function example1Action()
    {
        $builder = $this->modelsManager
            ->createBuilder();

        $builder = $builder->from('Hats');
        $builder = $builder->where('Hats.name = "panama"');
        $builder = $builder->orWhere('Hats.name = "baseball"');

        $query = $builder->getQuery();
        $generatedSql = $query->getSql()['sql'];
        $hats = $query->execute();

        $this->response->appendContent($generatedSql . '<br><br>');
        foreach ($hats as $hat) {
            $this->response->appendContent($hat->name . '<br>');
        }
        $this->response->send();
    }

    public function example2Action()
    {
        $hats = $this->modelsManager
            ->createBuilder()
            ->from('Hats')
            ->join('Colors')
            ->distinct(true)
            ->inWhere('Colors.name', ['black', 'red'])
            ->getQuery()
            ->execute();

        foreach ($hats as $hat) {
            $this->response->appendContent($hat->name . '<br>');
        }
        $this->response->send();
    }
}
