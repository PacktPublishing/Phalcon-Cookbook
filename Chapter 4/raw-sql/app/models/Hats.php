<?php

class Hats extends Phalcon\Mvc\Model
{
    public static function findFirstByRelatedColor($colorName)
    {
        $hat = new Hats();
        $color = new Colors();
        $hatColor = new HatsColors();

        $sql = <<<SQL
SELECT Hats.* FROM `{$hat->getSource()}` Hats
INNER JOIN `{$hatColor->getSource()}` HatsColors ON Hats.id = HatsColors.hats_id
INNER JOIN `{$color->getSource()}` Colors ON Colors.id = HatsColors.colors_id
WHERE Colors.name = :colorName
LIMIT 1
SQL;

        $connection = $hat->getReadConnection();
        $row = $connection->fetchOne($sql, Phalcon\Db::FETCH_ASSOC, [
            'colorName' => $colorName
        ]);
        if (!$row) {
            return false;
        }

        return Phalcon\Mvc\Model::cloneResultMap(new Hats(), $row,
            Phalcon\Mvc\Model::DIRTY_STATE_PERSISTENT);
    }


    public static function findByRelatedColor($colorName)
    {
        $hat = new Hats();
        $color = new Colors();
        $hatColor = new HatsColors();

        $sql = <<<SQL
SELECT Hats.*
FROM `{$hat->getSource()}` Hats
INNER JOIN `{$hatColor->getSource()}` HatsColors ON Hats.id = HatsColors.hats_id
INNER JOIN `{$color->getSource()}` Colors ON Colors.id = HatsColors.colors_id
WHERE Colors.name = :colorName
SQL;

        // We are getting the connection service from the model instead of from the
        // DI because each model can define its own connection service.
        $connection = $hat->getReadConnection();
        $results = $connection->query($sql, [
            'colorName' => $colorName
        ]);

        return new Phalcon\Mvc\Model\Resultset\Simple(null, $hat, $results);
    }

}
