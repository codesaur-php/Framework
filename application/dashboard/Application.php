<?php

namespace Dashboard;

use Raptor\Authentication\JWTAuthMiddleware;

class Application extends \Raptor\Application
{
    function __construct()
    {
        parent::__construct();
        
        $this->use(new JWTAuthMiddleware());
        
        $this->use(new Home\HomeRouter());
    }
}
