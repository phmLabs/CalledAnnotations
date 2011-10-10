<?php

namespace phmLabs\Base\Internal\Token;

class TokenizedClass
{
  private $tokens;

  public function __construct($tokens = array())
  {
    $this->tokens = $tokens;
  }

  public static function createFromFile($filename)
  {
    $tokens = token_get_all(file_get_contents($filename));
    return new self($tokens);
  }

  public function makeEvaluable()
  {
    $newTokens = array ();
    foreach ( $this->tokens as $token )
    {
      if (is_array($token))
      {
        if ($token[0] != T_OPEN_TAG)
        {
          $newTokens[] = $token;
        }
      }
      else
      {
        $newTokens[] = $token;
      }
    }
    $this->tokens = $newTokens;
  }

  public function isClass()
  {
    foreach ( $this->tokens as $token )
    {
      if ($token[0] == T_CLASS)
      {
        return true;
      }
    }
    return false;
  }

  public function rename($newname)
  {
    for($i = 0; $i < count($this->tokens); $i++)
    {
      if ($this->tokens[$i][0] == T_CLASS)
      {
        // @todo alle "unwichtigen" Tokens rauswerfen
        $this->tokens[$i + 2][1] = $newname;
        return;
      }
    }
  }

  public function evaluate()
  {
    $classContent = '';
    foreach ( $this->tokens as $token )
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

  /**
   * @todo sollte als iteratable
   */
  public function getTokens()
  {
    return $this->tokens;
  }

  public function removeClosingClassBracket()
  {
    $lastBracket = 0;
    for($i = 0; $i < count($this->tokens); $i++)
    {
      if ($this->tokens[$i] == "}")
      {
        $lastBracket = $i;
      }
    }
    unset($this->tokens[$lastBracket]);
  }
}