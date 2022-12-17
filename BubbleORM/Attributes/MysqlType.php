<?php
namespace BubbleORM\Attributes;

use Attribute;
use BubbleORM\Enums\MysqlTypeEnum;

#[Attribute(Attribute::TARGET_PROPERTY)]
class MysqlType{
    public function __construct(
        public readonly MysqlTypeEnum $type
    ){}
}