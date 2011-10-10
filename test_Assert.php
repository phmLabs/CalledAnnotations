<?php

use phmLabs\Annotation\Annotation\AnnotationHandler;
use phmLabs\Annotation\Example\Assert;
use phmLabs\Annotation\Autoload\Strategy\AnnotationStrategy;
use phmLabs\Autoload\Strategy\SimpleStrategy;
use phmLabs\Autoload\Autoloader;

include_once __DIR__.'/phmLabs/Autoload/Autoloader.php';

error_reporting(E_ALL);
ini_set( 'display_errors', 1);

date_default_timezone_set('Europe/Berlin');

$autoloader = new Autoloader();

$simpleStrategy = new SimpleStrategy();
$autoloader->registerNamespace('\phmLabs', $simpleStrategy);
$autoloader->registerNamespace('\Doctrine', $simpleStrategy);

$annotationStrategy = new AnnotationStrategy();

AnnotationHandler::registerAnnotation('assert');
AnnotationHandler::registerCallback('assert',
                                    AnnotationHandler::HOOK_TYPE_PRE,
                                    function($assoc_params, $annotation_values)
                                    {
                                      var_dump($assoc_params);
                                      var_dump($annotation_values);
                                    });

$autoloader->registerNamespace('\phmLabs\Annotation\Example', $annotationStrategy);

$assert = new Assert();
$assert->stringAsParameter('meinString');