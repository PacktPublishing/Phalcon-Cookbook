<?php

class Users extends Phalcon\Mvc\Model
{
    public function beforeValidationOnCreate()
    {
        if (!$this->hashPassword()) {
            return false;
        }
    }

    public function beforeValidationOnUpdate()
    {
        if ($this->hasChanged('password')) {
            if (!$this->hashPassword()) {
                return false;
            }
        }
    }

    protected function hashPassword()
    {
        $configPassword = $this->getDI()
            ->getConfig()
            ->application->password;

        $length = mb_strlen($this->password);
        if ($length < 4 || $length > 12) {
            $this->appendMessage(new Phalcon\Mvc\Model\Message('Invalid password length', 'password', 'InvalidValue'));
            return false;
        }

        $this->password = $this->getDI()
            ->getSecurity()
            ->hash($this->password);

        return true;
    }

    public function validation()
    {
        $validator = new Phalcon\Validation();

        $validator->add('username', new Phalcon\Validation\Validator\Uniqueness([
            'message' => 'The username already exists.'
        ]));

        return $this->validate($validator);
    }

    public function initialize(){
        $this->keepSnapshots(true);
    }
}
