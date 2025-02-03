<?php

namespace App\Controllers;

use Slim\Views\Twig;

class InvoicesController
{

    public function __construct(
        private readonly Twig $twig
    ){}



}