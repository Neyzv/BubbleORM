<?php
namespace BubbleORM\Attributes;

use Attribute;
use InvalidArgumentException;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Size{
    public function __construct(
        public readonly int $size
    ){
        if($this->size > 255 || $this->size < 0)
            throw new InvalidArgumentException("Value of attribute '". self::class ."' must be between 0 and 255...");
    }
}