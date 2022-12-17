<?php
namespace BubbleORM\Exceptions;

use Exception;
use Throwable;

class TableInformationsNotFoundException extends Exception{
    public function __construct($className, Throwable $previous = null)
    {
        parent::__construct("Class '$className' doesn't have a Table attribute specified...", 0, $previous);
    }
}