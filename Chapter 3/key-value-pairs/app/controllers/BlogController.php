<?php

class BlogController extends KeyValueControllerBase
{
    public function searchAction()
    {
        $params = $this->dispatcher->getParams();

        if (sizeof($params) === 0) {
            return 'Sorry: You must add at least one search criteria.';
        }

        // One of the key value pairs must be 'publisher/packt'
        if (!isset($params['publisher']) || strtolower($params['publisher']) !== 'packt') {
            return 'Sorry: Packt is the only publisher in this database.';
        }

        $output = "Searching By: <br><br>\n";
        foreach ($params as $key => $value) {
            $output .= "$key: $value<br>\n";
        }

        return $output;
    }
}
