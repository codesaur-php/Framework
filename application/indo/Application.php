<?php

namespace Indo;

class Application extends \Indoraptor\IndoApplication
{

    public function __construct()
    {
        parent::__construct();
        
        $this->use(new Mail\MailerRouter());
    }
}
