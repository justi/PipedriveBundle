<?php

namespace Justi\PipedriveBundle\DataObjects;


class PipedriveNote {

    public $id;
    public $deal_id;
    public $content;

    public function getId() {
        return $this->id;
    }
}