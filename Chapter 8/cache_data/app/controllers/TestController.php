<?php

class TestController extends Phalcon\Mvc\Controller
{
    public function indexAction()
    {
        $this->startTime = microtime(true);

        $records = Records::find();
        foreach ($records as $record) {
            $this->response->appendContent('<p>');
            $this->response->appendContent($record->id . ': ' . $record->val . '<br>');

            $links = $record->getLinks();

            foreach ($links as $link) {
                $this->response->appendContent('-- ' . $link->id . ': ' . $link->val . '<br>');
            }

            $this->response->appendContent('</p>');
        }

        $totalTime =  microtime(true) - $this->startTime;

        $this->response->appendContent('<div>total time: ' . round($totalTime, 5) . " seconds.</div>");
        $this->response->appendContent('<div>total queries: ' . $this->db->totalQueries . "</div>");

        $this->response->send();
    }

    public function cacheAction()
    {
        $this->getDI()->setShared('modelsMetadata', function () {
            return new Phalcon\Mvc\Model\MetaData\Apc([
                "lifetime" => 86400,
                "prefix"   => "cache_data"
            ]);
        });

        $this->startTime = microtime(true);

        $records = Records::find([
            'cache' => [
                'key' => 'all-records',
                'lifetime' => 15
            ]
        ]);
        foreach ($records as $record) {
            $this->response->appendContent('<p>');
            $this->response->appendContent($record->id . ': ' . $record->val . '<br>');

            $cache = $this->getDI()
                ->getModelsCache();

            $cacheKey = 'record-links-' . $record->id;
            $links = $cache->get($cacheKey);
            if (!$links) {
                $phql = <<<PHQL
SELECT Links.* FROM Links
JOIN RecordsLinks ON Links.id = RecordsLinks.linksId
JOIN Records ON Records.id = RecordsLinks.recordsId
WHERE Records.id = :recordsId:
PHQL;

                $query = new \Phalcon\Mvc\Model\Query($phql, $this->getDI());
                $links = $query->execute([
                    'recordsId' => $record->id,
                ]);

                $cache->save($cacheKey, $links);
            }

            // FIXME: We should be just doing this.
            // $links = $record->getLinks([
            //     'cache' => [
            //         'key' => 'record-links-' . $record->id,
            //         'lifetime' => 100
            //     ]
            // ]);

            foreach ($links as $link) {
                $this->response->appendContent('-- ' . $link->id . ': ' . $link->val . '<br>');
            }

            $this->response->appendContent('</p>');
        }

        $totalTime =  microtime(true) - $this->startTime;

        $this->response->appendContent('<div>total time: ' . round($totalTime, 8) . " seconds.</div>");
        $this->response->appendContent('<div>total queries: ' . $this->getDI()->getDb()->totalQueries . "</div>");

        $this->response->send();
    }
}
