<?php

namespace phmLabs\Annotation\Annotation;

interface DoctrineAnnotation
{
  public function __construct(array $data);

  public function __get($name);

  public function __set($name, $value);
}