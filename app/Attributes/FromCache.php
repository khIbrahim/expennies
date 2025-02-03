<?php

namespace App\Attributes;

#[\Attribute(\Attribute::TARGET_METHOD)]
class FromCache
{
    public function __construct(
        public string $key,
        public string $param = 'all',
        public int $expiration = 3600,
    ){}

}