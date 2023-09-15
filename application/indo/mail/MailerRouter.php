<?php

namespace Indo\Mail;

use codesaur\Router\Router;

class MailerRouter extends Router
{
    public function __construct()
    {
        $this->INTERNAL('/send/mail', [MailerController::class, 'send']);
    }
}
