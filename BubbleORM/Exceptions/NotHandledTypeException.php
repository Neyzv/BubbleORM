<?php
namespace BubbleORM\Exceptions;

use Exception;
use Throwable;

class NotHandledTypeException extends Exception{
    public function __construct($typeName, Throwable $previous = null)
    {
        parent::__construct("Type '$typeName' isn't handled yet...", 0, $previous);
    }
}