<?php

// @todo add persistenz layer (als dekorator)

namespace phmLabs\Annotation\Autoload\Strategy;

use Doctrine\Common\Annotations\AnnotationReader;
use phmLabs\Autoload\Strategy\Strategy;
use ReflectionClass, ReflectionMethod;

class AnnotationStrategy implements Strategy
{
  const TMP_CLASS_POSTFIX = '_phmCalledAnnotation';

  private $annotations = array ();
  private $classname;

  /**
   * This method is registred as spl_autoload function using the phmLabs autoload class.
   *
   * @param string $classname
   */
  public function autoload($classname)
  {
    $this->classname = $classname;
    $this->createTmpClass();
    $this->extractAnnotations();
    $this->createNewClass();
  }

  private function getFilename()
  {
    // @todo könnte noch verfeinert werden. siehe sf2, dort werden pfade zum namespace noch registriert
    return __DIR__ . '/../../../../' . str_replace('\\', DIRECTORY_SEPARATOR, $this->classname) . '.php';
  }

  private function getTokenizedClass()
  {
    $classContent = file_get_contents($this->getFilename());
    return token_get_all($classContent);
  }

  private function createTmpClass()
  {
    $classTokenized = $this->getTokenizedClass();
    for ($i = 0; $i < count($classTokenized); $i ++)
    {
      if ($classTokenized[$i][0] == T_OPEN_TAG)
      {
        unset($classTokenized[$i]);
      }
      elseif ($classTokenized[$i][0] == T_CLASS)
      {
        // @todo alle "unwichtigen" Tokens rauswerfen
        $className = $classTokenized[$i + 2][1];
        $classTokenized[$i + 2][1] = $className . self::TMP_CLASS_POSTFIX;
      }
    }
    $this->tokenToClass($classTokenized);
  }

  private function extractAnnotations()
  {
    $this->annotations = $this->getAnnotations($this->classname . self::TMP_CLASS_POSTFIX);
  }

  private function getAnnotations($classname)
  {
    // @todo abstrakte methoden können nicht annotiert werden
    $reflectedListener = new ReflectionClass($classname);
    $methods = $reflectedListener->getMethods();
    $this->annotationReader = new AnnotationReader();
    $this->annotationReader->setDefaultAnnotationNamespace('phmLabs\Annotation\Annotation\\');
    $this->annotationReader->setAutoloadAnnotations(false);

    foreach ($methods as $method)
    {
      $annotations[$method->name]['annotation'] = $this->annotationReader->getMethodAnnotations($method);
      $annotations[$method->name]['method'] = $method;
    }
    return $annotations;
  }

  private function createNewClass()
  {
    $annotations = $this->annotations;
    $classTokenized = $this->getTokenizedClass();

    if (count($annotations) == 0)
    {
      $this->tokenToClass($classTokenized);
    }
    else
    {
      $classContent = '';
      // @todo nur methoden, die auch überschrieben werden sollen
      for ($i = 0; $i < count($classTokenized); $i ++)
      {
        if (is_array($classTokenized[$i]))
        {
          if ($classTokenized[$i][0] == T_OPEN_TAG)
          {
            unset($classTokenized[$i]);
          }
          elseif ($classTokenized[$i][0] == T_FUNCTION)
          {
            $classContent .= $classTokenized[$i][1];
            $classTokenized[$i + 2][1] .= '_ANNOTETED_IGONORE_SUFFIX_ON_BUGFIXING';
          }
          else
          {
            $classContent .= $classTokenized[$i][1];
          }
        }
        else
        {
          $classContent .= $classTokenized[$i];
        }
      }

      foreach ($annotations as $functionName => $functionAnnotations)
      {
        // @todo static, public, abstract, parameter ...
        $classContent .= "\n  public function " . $functionName . "( )\n  { ";
        foreach ($functionAnnotations['annotation'] as $functionAnnotation)
        {
          $classContent .= "\n    \phmLabs\Annotation\Annotation\AnnotationHandler::triggerHook('" . get_class($functionAnnotation) . "', \phmLabs\Annotation\Annotation\AnnotationHandler::HOOK_TYPE_PRE);";
        }

        $classContent .= "\n    \$result = " . '$this->' . $functionName . '_ANNOTETED_IGONORE_SUFFIX_ON_BUGFIXING();';

        foreach ($functionAnnotations['annotation'] as $functionAnnotation)
        {
          $classContent .= "\n    \phmLabs\Annotation\Annotation\AnnotationHandler::triggerHook('" . get_class($functionAnnotation) . "', \phmLabs\Annotation\Annotation\AnnotationHandler::HOOK_TYPE_POST);";
        }
        $classContent .= "\n    return \$result;";
        $classContent .= "\n  }";
      }

      $classContent .= "\n}";

      eval($classContent);
    }
  }

  private function tokenToClass($tokens)
  {
    $classContent = '';

    foreach ($tokens as $token)
    {
      if (is_array($token))
      {
        $classContent .= $token[1];
      }
      else
      {
        $classContent .= $token;
      }
    }
    eval($classContent);
  }
}