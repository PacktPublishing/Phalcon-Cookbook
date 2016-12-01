<?php

class PhqlController extends Phalcon\Mvc\Controller
{
    public function basicJoinAction()
    {
        $phql = "SELECT Hats.* FROM Hats JOIN Colors WHERE Colors.name = 'red'";
        $query = new Phalcon\Mvc\Model\Query($phql, $this->getDI());
        $this->response->appendContent('<p>' . $query->getSql()['sql'] . '</p>');

       $hats = $query->execute();
       $this->response->appendContent('<ul>');
       foreach ($hats as $hat) {
           $this->response->appendContent('<li>' . $hat->name . '</li>');
       }
       $this->response->appendContent('</ul>');

       $this->response->send();
   }

   public function noImplicitJoinsAction()
   {
       $phql = <<<PHQL
SELECT Hats.* FROM Hats
INNER JOIN HatsColors ON HatsColors.hats_id = Hats.id
INNER JOIN Colors ON Colors.id = HatsColors.colors_id
WHERE Colors.name = 'red'
PHQL;
        $query = new Phalcon\Mvc\Model\Query($phql, $this->getDI(), [
            'enable_implicit_joins' => false
        ]);
        $this->response->appendContent('<p>' . $query->getSql()['sql'] . '</p>');

        $hats = $query->execute();
        $this->response->appendContent('<ul>');
        foreach ($hats as $hat) {
            $this->response->appendContent('<li>' . $hat->name . '</li>');
        }
        $this->response->appendContent('</ul>');

        $this->response->send();
    }

    public function bindSimpleAction()
    {
        $phql = "SELECT Hats.* FROM Hats JOIN Colors WHERE Colors.name = :color:";
        $query = new Phalcon\Mvc\Model\Query($phql, $this->getDI(), [
            'phqlLiterals' => false
        ]);
        $this->response->appendContent('<p>' . $query->getSql()['sql'] . '</p>');

        $hats = $query->execute([
            'color' => 'red'
        ]);

        $this->response->appendContent('<ul>');
        foreach ($hats as $hat) {
            $this->response->appendContent('<li>' . $hat->name . '</li>');
        }
        $this->response->appendContent('</ul>');

        $this->response->send();
    }

    public function bindArrayAction()
    {
        $phql = "SELECT Hats.* FROM Hats JOIN Colors WHERE Colors.name IN ({colors:array})";
        $query = new Phalcon\Mvc\Model\Query($phql, $this->getDI());

        $query->setBindParams([
          'colors' => ['red', 'green', 'black']
        ]);
        $hats = $query->execute();

        $this->response->appendContent('<ul>');
        foreach ($hats as $hat) {
            $this->response->appendContent('<li>' . $hat->name . '</li>');
        }
        $this->response->appendContent('</ul>');

        $this->response->send();
    }
}
