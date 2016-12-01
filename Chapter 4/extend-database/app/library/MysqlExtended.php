<?php

class MysqlExtended extends Phalcon\Db\Dialect\Mysql
{
    public function getSqlExpression(array $expression, $escapeChar = null, $bindCounts = null)
    {
        if ($expression["type"] == 'functionCall') {
            switch ($expression["name"]) {
                case 'FULLTEXT_MATCH':
                    if (count($expression["arguments"]) < 2) {
                        throw new \Exception('FULLTEXT_MATCH requires 2 parameters');
                    }
                    $arguments = array();
                    $length = count($expression["arguments"]) - 1;
                    for ($i = 0; $i < $length; $i++) {
                        $arguments[] = $this->getSqlExpression($expression["arguments"][$i]);
                    }
                    return 'MATCH(' . join(', ', $arguments) . ') AGAINST (' .
                    $this->getSqlExpression($expression["arguments"][$length]) . ')';
                case 'FULLTEXT_MATCH_BMODE':
                    if (count($expression["arguments"]) < 2) {
                        throw new \Exception('FULLTEXT_MATCH requires 2 parameters');
                    }
                    $arguments = array();
                    $length = count($expression["arguments"]) - 1;
                    for ($i = 0; $i < $length; $i++) {
                        $arguments[] = $this->getSqlExpression($expression["arguments"][$i]);
                    }
                    return 'MATCH(' . join(', ', $arguments) . ') AGAINST (' .
                    $this->getSqlExpression($expression["arguments"][$length]) . ' IN BOOLEAN MODE)';
            }
        }
        return parent::getSqlExpression($expression, $escapeChar);
    }
}
