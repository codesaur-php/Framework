<?php

namespace App\Blog\Home;

use codesaur\Router\Router;

class HomeRouter extends Router
{
    function __construct()
    {
        $this->GET('/', [HomeController::class, 'index'])->name('home');
    }
}
