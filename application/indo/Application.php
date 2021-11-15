<?php

namespace App\Indo;

use Indoraptor\PDOConnectMiddleware;

class Application extends \Indoraptor\IndoApplication
{
    function __construct()
    {
        parent::__construct();
        
        $this->use(new PDOConnectMiddleware());
    }
}
