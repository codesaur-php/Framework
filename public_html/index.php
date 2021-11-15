<?php

use codesaur\Http\Message\ServerRequest;

require_once '../application/bootstrap.php';

$request = (new ServerRequest())->initFromGlobal();
$uri_path = rawurldecode($request->getUri()->getPath());
$script_path = dirname($request->getServerParams()['SCRIPT_NAME']);
if ($script_path == '\\' || $script_path == '/') {
    $script_path = '';
}
if (!empty($script_path)) {
    $uri_path = substr($uri_path, strlen($script_path));
}
if (empty($uri_path)) {
    $uri_path = '/';
}
$pipe = explode('/', $uri_path)[1];
if ($pipe == 'indo') {
    $script_path .= "/$pipe";    
    $application = new Indo\Application();
    $application->use(new Indoraptor\IndoExceptionHandler());
    $application->use(new Indoraptor\JsonResponseMiddleware());
} else {
    if ($pipe == 'dashboard') {
        $script_path .= "/$pipe";
        $application = new Dashboard\Application();
    } else {
        $application = new Blog\Application();
    }
    $request = $request->withAttribute('indo', new Indo\Application());
}
$request->setScriptTargetPath($script_path);

$application->handle($request);
