<?php

class SqlController extends Phalcon\Mvc\Controller
{

    public function example1Action()
    {
        $hat = new Hats();
        $color = new Colors();
        $hatColor = new HatsColors();

        $sql = <<<SQL
SELECT Hats.*
FROM `{$hat->getSource()}` Hats
INNER JOIN `{$hatColor->getSource()}` HatsColors ON Hats.id = HatsColors.hats_id
INNER JOIN `{$color->getSource()}` Colors ON Colors.id = HatsColors.colors_id
WHERE Colors.name = :color
SQL;

        $results = $hat->getReadConnection()
            ->query($sql, [
                'color' => 'black'
            ]);

        $resultset = new Phalcon\Mvc\Model\Resultset\Simple(null, $hat, $results);
        foreach ($resultset as $record) {
            $this->response->appendContent($record->name);
            $this->response->appendContent('<br>');
        }
        $this->response->send();
    }




    public function example2Action()
    {
        $hat = new Hats();
        $color = new Colors();
        $hatColor = new HatsColors();

        $sql = <<<SQL
SELECT Hats.* FROM `{$hat->getSource()}` Hats
INNER JOIN `{$hatColor->getSource()}` HatsColors ON Hats.id = HatsColors.hats_id
INNER JOIN `{$color->getSource()}` Colors ON Colors.id = HatsColors.colors_id
WHERE Colors.name = :color
SQL;

        $rawResults = $hat->getReadConnection()
            ->query($sql, [
                'color' => 'black'
            ])
            ->fetchAll(Phalcon\Db::FETCH_ASSOC);

        $results = [];
        foreach ($rawResults as $row) {
            $results[] = Phalcon\Mvc\Model::cloneResultMap(new Hats(), $row, Phalcon\Mvc\Model::DIRTY_STATE_PERSISTENT);
        }

        foreach ($results as $row) {
            $this->response->appendContent($row->name);
            $this->response->appendContent('<br>');
        }
        $this->response->send();
    }




    public function example3Action()
    {
        $hat = new Hats();
        $color = new Colors();
        $hatColor = new HatsColors();

        $sql = <<<SQL
SELECT Hats.name as hats_name, Colors.name colors_name
FROM `{$hat->getSource()}` Hats
INNER JOIN `{$hatColor->getSource()}` HatsColors ON Hats.id = HatsColors.hats_id
INNER JOIN `{$color->getSource()}` Colors ON Colors.id = HatsColors.colors_id
WHERE Colors.name = :color
SQL;

        $results = $hat->getReadConnection()
            ->query($sql, [
                'color' => 'black'
            ]);
        $results->setFetchMode(\Phalcon\Db::FETCH_ASSOC);

        while ($row = $results->fetchArray()) {
            $this->response->appendContent($row['hats_name'] . ': ' . $row['colors_name']);
            $this->response->appendContent('<br>');
        }

        $this->response->send();
    }

}
