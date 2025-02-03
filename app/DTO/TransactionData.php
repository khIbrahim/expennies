<?php

namespace App\DTO;

use App\Entity\Category;

class TransactionData
{

    public function __construct(
        public string    $description,
        public float     $amount,
        public \DateTime $date,
        public Category  $category
    ){}

}