<?php

namespace Signifly\EventSourceGenerator;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Signifly\EventSourceGenerator\EventSourceGeneratorClass
 */
class EventSourceGeneratorFacade extends Facade
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
