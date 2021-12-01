<?php

namespace Dashboard;

class Application extends \Raptor\Application
{
    function __construct()
    {
        parent::__construct();
        
        $this->use(new Home\HomeRouter());
    }
}
