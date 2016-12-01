<?php

class Entries extends Phalcon\Mvc\Model
{
    public $id;

    public $uuid;

    private $value;

    public function getValue()
    {
        return $this->value;
    }

    protected function retrieveValue()
    {
        $deepDirectory = $this->getDeepDirectory();
        if (!file_exists($deepDirectory)) {
            throw new \Exception('The deep directory does not exist.');
        }

        return file_get_contents($deepDirectory . '/' . $this->uuid);
    }

    protected function getDeepDirectory()
    {
        $dataDir = $this->getDI()
            ->getConfig()
            ->application->dataDir;

        $deepDir = $dataDir;
        for ($i = 0; $i < 3; $i++) {
            $deepDir .= '/' . $this->uuid[$i];
        }
        return $deepDir;
    }

    protected function beforeValidationOnCreate()
    {
        $data = openssl_random_pseudo_bytes(16);
        $base16 = bin2hex($data);
        $base62 = gmp_strval(gmp_init($base16, 16), 62);
        $padded = str_pad($base62, 22, '0', STR_PAD_LEFT);
        $this->uuid = vsprintf('%s%s%s-%s%s%s%s%s-%s%s%s', str_split($padded, 2));
    }

    protected function afterCreate()
    {
        $deepDirectory = $this->getDeepDirectory();
        if (!file_exists($deepDirectory)) {
            mkdir($deepDirectory, 0770, true);
        }
        file_put_contents($deepDirectory . '/' . $this->uuid, rand(1, 1000));
    }

    protected function afterDelete()
    {
        // See if you can do this one on your own.
        // Make sure to please not recursively delete your
        // entire file system, project or data folder!!!
    }

    protected function afterFetch()
    {
        $this->value = $this->retrieveValue();
    }

    protected function initialize()
    {
        $this->skipAttributes(['value']);
    }

}
