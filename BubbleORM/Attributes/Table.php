<?php

namespace BubbleORM\Attributes;

use Attribute;
use InvalidArgumentException;

#[Attribute(Attribute::TARGET_CLASS)]
class Table{
    public function __construct(
        public readonly string $tableName
    ) {
        if(empty($this->tableName) || preg_match("/[^A-z]/", $this->tableName))
            throw new InvalidArgumentException("Invalid table name $this->tableName, table name can not be null, empty or contain specials characters...");
    }
}