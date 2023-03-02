<?php

namespace Blog;

use codesaur\Http\Application\ExceptionHandler;

use Raptor\Localization\LocalizationMiddleware;
use Raptor\Contents\SettingsMiddleware;

class Application extends \codesaur\Http\Application\Application
{
    public function __construct()
    {
        parent::__construct();
        
        $this->use(new ExceptionHandler());
        
        $this->use(new LocalizationMiddleware());
        $this->use(new SettingsMiddleware());

        $this->use(new Home\HomeRouter());
    }
}
