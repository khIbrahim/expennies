<?php

namespace App\DTO;

class DatatableQueryParams
{

    public function __construct(
        public readonly int    $start,
        public readonly int    $length,
        public readonly string $orderBy,
        public readonly string $orderDir,
        public readonly string $searchTerm,
        public readonly int    $draw
    ){}

}