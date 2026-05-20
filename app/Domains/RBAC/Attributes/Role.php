<?php

namespace App\Domains\RBAC\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Role
{
    public function __construct(public array|string $roles)
    {
        if (is_string($this->roles)) {
            $this->roles = [$this->roles];
        }
    }
}
