<?php

class SqlController extends Phalcon\Mvc\Controller
{
    public function findFirstByRelatedColorAction()
    {
        $colorName = $this->request->getQuery('color', null, 'black');

        $hat = Hats::findFirstByRelatedColor($colorName);
        $this->response->appendContent($hat->name);
        $this->response->send();
    }

    public function findByRelatedColorAction()
    {
        $colorName = $this->request->getQuery('color', null, 'black');

        $resultset = Hats::findByRelatedColor($colorName);
        foreach ($resultset as $record) {
            $this->response->appendContent($record->name);
            $this->response->appendContent('<br>');
        }
        $this->response->send();
    }

    public function lowLevelAction()
    {
        $colorName = $this->request->getQuery('color', null, 'black');

        $hat = new Hats();
        $color = new Colors();
        $hatColor = new HatsColors();

        $sql = <<<SQL
SELECT Hats.name as hats_name, Colors.name colors_name
FROM `{$hat->getSource()}` Hats
INNER JOIN `{$hatColor->getSource()}` HatsColors ON Hats.id = HatsColors.hats_id
INNER JOIN `{$color->getSource()}` Colors ON Colors.id = HatsColors.colors_id
WHERE Colors.name = :colorName
SQL;

        $dbh = $hat->getReadConnection()
            ->getInternalHandler();
        $stmt = $dbh->prepare($sql);
        $stmt->execute([
            'colorName' => $colorName
        ]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($results as $row) {
            $this->response->appendContent($row['hats_name'] . ': ' . $row['colors_name']);
            $this->response->appendContent('<br>');
        }

        $this->response->send();
    }

}
