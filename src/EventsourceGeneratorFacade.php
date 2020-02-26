<?php

namespace Signifly\EventsourceGenerator;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Signifly\EventsourceGenerator\EventsourceGeneratorClass
 */
class EventsourceGeneratorFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'eventsource-generator';
    }
}
