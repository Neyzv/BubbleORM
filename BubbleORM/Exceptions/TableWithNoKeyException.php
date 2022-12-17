<?php
namespace BubbleORM\Exceptions;

use Exception;
use Throwable;

class TableWithNoKeyException extends Exception{
    public function __construct($className, Throwable $previous = null)
    {
        parent::__construct("Class '$className' doesn't have any Key attribute specified, must have one minimum...", 0, $previous);
    }
}