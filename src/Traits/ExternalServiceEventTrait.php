<?php

namespace aliirfaan\CitronelExternalService\Traits;

trait ExternalServiceEventTrait
{
    public $eventData;

    public function __construct($eventData)
    {
        $this->eventData = $eventData;
    }
}
