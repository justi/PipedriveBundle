<?php

namespace Justi\PipedriveBundle\DataObjects;


class PipedrivePerson {

    public $id;
    public $name;
    public $email;
    public $org_id;
    public $phone;

    public function getId() {
        return $this->id;
    }

    public function getEmail() {
        return $this->email;
    }
}