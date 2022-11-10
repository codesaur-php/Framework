<?php

namespace Dashboard\Home;

use Raptor\Dashboard\DashboardController;

class HomeController extends DashboardController
{
    public function index()
    {
        $this->twigDashboard(dirname(__FILE__) . '/home.html')->render();
    }
}
