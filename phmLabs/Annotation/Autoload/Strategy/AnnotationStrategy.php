<?php

// @todo add persistenz layer (als dekorator)


namespace phmLabs\Annotation\Autoload\Strategy;

use phmLabs\Base\Internal\Namespaces\NamespaceExtractor;

use phmLabs\Base\Internal\Token\TokenizedClass;

use Doctrine\Common\Annotations\AnnotationReader;
use phmLabs\Autoload\Strategy\Strategy;
use ReflectionClass, ReflectionMethod;

class AnnotationStrategy implements Strategy
{
  /**
   * The classname decorator for the temporary created class
   *
   * @var string
   */
  const TMP_CLASS_POSTFIX = '_phmCalledAnnotation';

  /**
   * The found annotations
   *
   * @var mixed[]
   */
  private $annotations = array ();

  /**
   * The name of the class to be autoloaded
   *
   * @var string
   */
  private $classname;

  private $tokenizedClass;

  /**
   * This method is registred as spl_autoload function using the phmLabs autoload class.
   *
   * @param string $classname
   */
  public function autoload($classname)
  {
    $this->reset();

    $tokenizedClass = $this->getTokenizeClass($this->getFilename($classname));

    if (!$tokenizedClass->isClass())
    {
      $tokenizedClass->evaluate( );
      return true;
    }
    else
    {
      $this->createTmpClass($tokenizedClass, $classname);
      $annotations = $this->getAnnotations($this->getTmpClassName($classname),
                                           NamespaceExtractor::fromClassname($classname));
      $this->createNewClass($annotations, $tokenizedClass, $classname);
    }
  }

  private function reset()
  {
    $this->classname = '';
    unset($this->tokenList);
  }

  /**
   * Returns the filename according to the given classname (autoload)
   *
   * @return string
   */
  private function getFilename($classname)
  {
    // @todo könnte noch verfeinert werden. siehe sf2, dort werden pfade zum namespace noch registriert
    return __DIR__ . '/../../../../' . str_replace('\\', DIRECTORY_SEPARATOR, $classname) . '.php';
  }

  /**
   * Returns the tokenized class
   *
   * @return mixed[] the tokens
   */
  private function getTokenizeClass($filename)
  {
    $tokenizedClass = TokenizedClass::createFromFile($filename);
    $tokenizedClass->makeEvaluable();
    return $tokenizedClass;
  }

  private function tokenToString($tokens)
  {
    $classContent = '';
    foreach ( $tokens as $token )
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
    return $classContent;
  }

  private function getTmpClassName($classname)
  {
    return 'phmLabs_' . str_replace('\\', '_', $classname);
  }

  private function createTmpClass($tokenizedClass, $classname)
  {
    $tmpClass = clone ($tokenizedClass);
    $tmpClass->rename($this->getTmpClassName($classname));
    $tmpClass->evaluate();
  }

  private function getAnnotations($classname, $namespace)
  {
    $reflectedListener = new ReflectionClass($namespace. "\\" .$classname);
    $methods = $reflectedListener->getMethods();
    $this->annotationReader = new AnnotationReader();
    $this->annotationReader->setDefaultAnnotationNamespace('phmLabs\Annotation\Annotation\\');
    $this->annotationReader->setAutoloadAnnotations(false);

    foreach ( $methods as $method )
    {
      $annotations[$method->name]['annotation'] = $this->annotationReader->getMethodAnnotations($method);
      $annotations[$method->name]['method'] = $method;
    }
    return $annotations;
  }

  private function createNewClass($annotations, $classTokenized, $classname)
  {
    if (count($annotations) == 0)
    {
      $classTokenized->evaluate();
      return;
    }

    $classContent = '';

    $classTokenized->removeClosingClassBracket();
    $tokens = $classTokenized->getTokens();

    for($i = 0; $i < count($tokens); $i++)
    {
      if (is_array($tokens[$i]))
      {
        if ($tokens[$i][0] == T_OPEN_TAG)
        {
          unset($tokens[$i]);
        }
        elseif ($tokens[$i][0] == T_FUNCTION)
        {
          // @todo nur methoden, die auch überschrieben werden sollen
          $classContent .= $tokens[$i][1];
          $tokens[$i + 2][1] .= '_ANNOTETED_IGNORE_SUFFIX_ON_BUGFIXING';
        }
        else
        {
          $classContent .= $tokens[$i][1];
        }
      }
      else
      {
        $classContent .= $tokens[$i];
      }
    }

    foreach ( $annotations as $functionName => $functionAnnotations )
    {
      // @todo static, public, abstract, parameter ...
      $classContent .= "\n  public function " . $functionName . "( )\n  { ";
      $classContent .= "\n    \$parameters = func_get_args();";
      foreach ( $functionAnnotations['annotation'] as $functionAnnotation )
      {
        // @todo named parameters
        $classContent .= "\n    \\phmLabs\\Annotation\\Annotation\\AnnotationHandler::triggerHook('" . get_class($functionAnnotation) . "', \\phmLabs\\Annotation\\Annotation\\AnnotationHandler::HOOK_TYPE_PRE, \$parameters);";
      }

      if ($functionAnnotations['method']->isStatic())
      {
        $object = '"' . $classname . '"';
      }
      else
      {
        $object = '$this';
      }

      $classContent .= "\n    \$result = call_user_func_array( array( " . $object . ", '" . $functionName . "_ANNOTETED_IGNORE_SUFFIX_ON_BUGFIXING'), \$parameters);";

      foreach ( $functionAnnotations['annotation'] as $functionAnnotation )
      {
        $classContent .= "\n    \\phmLabs\\Annotation\\Annotation\\AnnotationHandler::triggerHook('" . get_class($functionAnnotation) . "', \\phmLabs\\Annotation\\Annotation\\AnnotationHandler::HOOK_TYPE_POST, \$parameters);";
      }
      $classContent .= "\n    return \$result;";
      $classContent .= "\n  }";
    }

    $classContent .= "\n}";
    eval($classContent);
  }

  private function tokenToClass($tokens)
  {
    $classContent = $this->tokenToString($tokens);
    eval($classContent);
  }
}