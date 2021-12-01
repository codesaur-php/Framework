<?php

namespace Indo;

class Application extends \Indoraptor\IndoApplication
{
    function __construct(bool $is_application_json)
    {
        parent::__construct($is_application_json);
    }
}
