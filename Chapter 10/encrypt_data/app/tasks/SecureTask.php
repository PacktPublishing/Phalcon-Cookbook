<?php

class SecureTask extends Phalcon\Cli\Task
{
    public function encryptAction($args)
    {
        if (count($args) < 2) {
            error_log('Encryption requires an input and output file parameter.');
            exit(1);
        }

        $inputFile = $args[0];
        $outputFile = $args[1];

        $input = file_get_contents($inputFile);
        if ($input === false) {
            error_log('The source data file could not be read.');
            return;
        }

        $output = $this->getDI()
            ->getCrypt()
            ->encryptBase64($input);

        if (file_put_contents($outputFile, $output) === false) {
            error_log('The encrypted data could not be written.');
            return;
        }
    }

    public function decryptAction($args)
    {
        if (count($args) < 2) {
            error_log('Decryption requires an input and output file parameter.');
            exit(1);
        }

        $inputFile = $args[0];
        $outputFile = $args[1];

        $input = file_get_contents($inputFile);
        if ($input === false) {
            error_log('The encrypted file could not be read.');
            return;
        }

        $output = $this->getDI()
            ->getCrypt()
            ->decryptBase64($input);

        if (file_put_contents($outputFile, $output) === false) {
            error_log('The decrypted data could not be written.');
            return;
        }
    }

}
