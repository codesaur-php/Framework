<?php

namespace Blog;

use codesaur\Http\Application\ExceptionHandler;

class Application extends \codesaur\Http\Application\Application
{
    public function __construct()
    {
        parent::__construct();
        
        $this->use(new ExceptionHandler());
        
        $this->use(new Home\HomeRouter());
    }
}
