<?php

namespace BubbleORM\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Key {
    public function __construct(
        public readonly bool $autoIncrement = true
    ) {}
}