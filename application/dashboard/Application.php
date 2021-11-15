<?php

namespace Dashboard;

use Raptor\Exception\ErrorHandler;
use Raptor\Authentication\SessionMiddleware;
use Raptor\Authentication\JWTAuthMiddleware;
use Raptor\Authentication\LocalizationMiddleware;

class Application extends \Raptor\Application
{
    function __construct()
    {
        parent::__construct();
        
        $this->use(new ErrorHandler());
        
        $this->use(new SessionMiddleware());
        $this->use(new JWTAuthMiddleware());
        $this->use(new LocalizationMiddleware());
        
        $this->use(new Home\HomeRouter());
    }
}
