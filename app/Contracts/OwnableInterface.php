<?php

namespace App\Contracts;

interface OwnableInterface
{

    public function getUser(): UserInterface;

}