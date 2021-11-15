<?php

namespace App\Blog;

use codesaur\Http\Application\ExceptionHandler;

class Application extends \codesaur\Http\Application\Application
{
    function __construct()
    {
        parent::__construct();
        
        $this->use(new ExceptionHandler());
        
        $this->use(new Home\HomeRouter());
    }
}
