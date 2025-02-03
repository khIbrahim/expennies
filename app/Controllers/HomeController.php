<?php

declare(strict_types = 1);

namespace App\Controllers;

use App\ResponseFormatter;
use App\Services\CategoryService;
use App\Services\TransactionsService;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class HomeController
{
    public function __construct(
        private readonly Twig $twig,
        private readonly CategoryService $categoryService,
        private readonly TransactionsService $transactionsService,
        private readonly ResponseFormatter $responseFormatter,
    ){}

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function index(Response $response): Response
    {
        $topSpendingCategories = $this->categoryService->getTopSpendingCategories(4);
        $recentTransactions    = $this->transactionsService->getRecentTransactions(4);
        $summary               = $this->transactionsService->getSummary()[0];

        return $this->twig->render($response, 'dashboard.twig', [
            'topSpendingCategories' => $topSpendingCategories,
            'recentTransactions'    => $recentTransactions,
            'summary'               => $summary
        ]);
    }

    public function statsMonthlySummaryChart(Response $response): Response
    {
        $summary = $this->transactionsService->getMonthlySummary(2024);

        return $this->responseFormatter->asJson($response, $summary);
    }

}
