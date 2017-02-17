<?php

namespace Justi\PipedriveBundle\DataObjects;


class PipedriveDeal {

    public $id;
    public $deal_title;
    public $org_id;

    public $title;
    public $user_id;
    public $person_id;
    public $stage_id;
    public $status;
    public $lost_reason;

    public function getId() {
        return $this->id;
    }
}