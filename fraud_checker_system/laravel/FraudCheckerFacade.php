<?php

namespace FraudChecker\Laravel;

use Illuminate\Support\Facades\Facade;

class FraudCheckerFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'fraud-checker';
    }
}
