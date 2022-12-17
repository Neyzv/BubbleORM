<?php

namespace BubbleORM\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class DefaultValue{
    public function __construct(
        public readonly mixed $defaultValue
    ) {}
}