<?php

class QueryController extends Phalcon\Mvc\Controller
{
    public function indexAction()
    {
        $sql = "SELECT * FROM Posts WHERE FULLTEXT_MATCH_BMODE(text, '+technology')";
        $query = new Phalcon\Mvc\Model\Query($sql, $this->getDI());
        $posts = $query->execute();

        foreach ($posts as $post) {
            $this->response->appendContent("{$post->id}: {$post->text}<br>");
        }
        $this->response->send();
    }
}
