<?php

use phmLabs\Annotation\Annotation\AnnotationHandler;
use phmLabs\Annotation\Example\Deprecated;
use phmLabs\Annotation\Autoload\Strategy\AnnotationStrategy;
use phmLabs\Autoload\Strategy\SimpleStrategy;
use phmLabs\Autoload\Autoloader;

include_once __DIR__.'/phmLabs/Autoload/Autoloader.php';

$autoloader = new Autoloader();

$simpleStrategy = new SimpleStrategy();
$autoloader->registerNamespace('\phmLabs', $simpleStrategy);
$autoloader->registerNamespace('\Doctrine', $simpleStrategy);

$annotationStrategy = new AnnotationStrategy();

AnnotationHandler::registerAnnotation('deprecated');
AnnotationHandler::registerCallback('deprecated', AnnotationHandler::HOOK_TYPE_PRE, function($params){ echo 'PRE'; });
AnnotationHandler::registerCallback('deprecated', AnnotationHandler::HOOK_TYPE_POST, function($params){ echo 'POST'; });

$autoloader->registerNamespace('\phmLabs\Annotation\Example', $annotationStrategy);

$testObject = new Deprecated();
$testObject->deprecatedFunction();