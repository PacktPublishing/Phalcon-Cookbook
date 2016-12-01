
## Securing data with encryption

In this recipe we will create a solution for encrypting and decrypting data.  This could be useful for creating a storage vault type of website that allows users to securely store their data in such a way that even the admins cannot access it or it could also be used to securely store valuable data on a remote machine.  This recipe will use a reversible process so that the data can be decrypted after being encrypted.  Although it is not necessary to understand the miraculous math involved with this process it is vital to understand one critical concept: Don't lose the encryption key or give anyone access to it.  Other than that big one its all just good times.

#### Getting Ready...

This recipe uses the **Phalcon Developer Tools** which we will use to setup a project skeleton.

#### How to do it...
Follow these steps to complete this recipe…

1) We need to have an application skeleton for experimentation.  If you already have such an application, you can skip this step. Create a project skeleton using the "cli" template:

`phalcon project encrypt_data cli`

2) Add the following encryption key setting to your configuration file at `app/config/config.php`:

```php
'security' => [
    // Change Key
    'key' => '%31.1e$i86e$f!8jz'
]
```

3) Create a crypt service in `app/config/services.php`:

```php
$di->setShared('crypt', function () {
    $config = $this->getConfig();

    $crypt = new Phalcon\Crypt();
    $crypt->setKey($config->security->key);

    return $crypt;
});
```

4) Create the task `app/tasks/SecureTask.php`:

```php
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
```

5) Create a test data file in the root of the project directory named `data.txt` and put in some interesting text in there like "This is important data!".

6) Run the following commands:

`./run secure encrypt data.txt enc.txt`

There should now be a "enc.txt" file that contains some scrambled data.

`./run secure decrypt enc.txt dec.txt`

The "dec.txt" file should contain our original data.

#### How it works...

The math behind this recipe is incredibly complex but from just an API viewpoint it is exceedingly simple.  First lets look at the SecureTask class.  We'll skip over the basic checking routines as they are typical and self-explanatory.

First we need to retrieve the data from the file system.  While there are more complicated and flexible stream based methods the following approach is quite nice due to its simplicity.

```php
$input = file_get_contents($inputFile);
```

Next use our 'crypt' service to encrypt the data using a base 64 number system.

```php
$output = $this->getDI()
    ->getCrypt()
    ->encryptBase64($input);
```

Finally we save the encrypted data to disk with:

```php
file_put_contents($outputFile, $output)
```

The decryption process is incredibly similar and so I'll abbreviate the entire method into just 3 calls.  We can easily see that we are simply reading the file, using the crypt service to decrypt it and then saving it to the file system.

```php
$input = file_get_contents($inputFile);

$output = $this->getDI()
    ->getCrypt()
    ->decryptBase64($input);

file_put_contents($outputFile, $output)
```

So its as easy as that.  Note that in our recipe we are only working with text data and with more complicated approaches we could read the data in binary format and to operate on it in batches while streaming it back out to the filesystem.
