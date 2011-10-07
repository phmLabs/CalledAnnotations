<?php

namespace phmLabs\Annotation\Annotation;

interface Annotation extends DoctrineAnnotation
{
  public function call($type);
}