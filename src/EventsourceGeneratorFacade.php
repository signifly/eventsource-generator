<?php

namespace Signifly\EventSourceGenerator;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Signifly\EventSourceGenerator\EventSourceGenerator
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
