<?php

namespace App\Controllers;

use App\Contracts\EntityManagerServiceInterface;
use App\Contracts\RequestValidatorFactoryInterface;
use App\DTO\TransactionData;
use App\Entity\Receipt;
use App\Entity\Transaction;
use App\RequestValidators\TransactionRequestValidator;
use App\ResponseFormatter;
use App\Services\CategoryService;
use App\Services\RequestService;
use App\Services\TransactionsService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class TransactionsController
{

    public function __construct(
        private readonly TransactionsService $transactionsService,
        private readonly Twig $twig,
        private readonly RequestService $requestService,
        private readonly ResponseFormatter $responseFormatter,
        private readonly CategoryService $categoryService,
        private readonly RequestValidatorFactoryInterface $requestValidatorFactory,
        private readonly EntityManagerServiceInterface $entityManagerService
    ){}

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function index(Response $response): Response
    {
        return $this->twig->render(
            $response,
            'transactions/index.twig',
            [
                'transactions' => $this->transactionsService->getAll(),
                'categories'   => $this->categoryService->getAll()
            ]
        );
    }

    public function load(Request $request, Response $response): Response
    {
        $params = $this->requestService->getDatatableQueryParams($request);
        $transactions = $this->transactionsService->getPaginated($params);

        $formatter = function(Transaction $transaction){
            return [
                'id' => $transaction->getId(),
                'description' => $transaction->getDescription(),
                'amount' => $transaction->getAmount(),
                'category' => $transaction->getCategory()->getName(),
                'receipts' => array_map(function(Receipt $receipt){
                    return [
                        'id' => $receipt->getId(),
                        'name' => $receipt->getFilename(),
                    ];
                }, (array) $transaction->getReceipts()->getIterator()),
                'date' => $transaction->getDate()->format('m/d/Y g:i A'),
                'wasReviewed' => $transaction->wasReviewed()
            ];
        };

        $totalTransactions = count($transactions);

        return $this->responseFormatter->asDatatable(
            $response,
            array_map($formatter, (array) $transactions->getIterator()),
            $params->draw,
            $totalTransactions
        );
    }

    public function delete(Response $response, Transaction $transaction): Response
    {
        $this->entityManagerService->delete($transaction, true);

        return $response;
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $this->requestValidatorFactory->make(TransactionRequestValidator::class)
            ->validate($request->getParsedBody());
        $user = $request->getAttribute('user');

        $transaction = $this->transactionsService->create(new TransactionData(
            description: $data['description'],
            amount: $data['amount'],
            date: new \DateTime($data['date']),
            category: $data['category']
        ), $user);
        $this->entityManagerService->sync($transaction);

        return $response->withHeader('Location', '/transactions')->withStatus(302);
    }

    public function get(Response $response, Transaction $transaction): Response
    {
        $data = [
            'id' => $transaction->getId(),
            'description' => $transaction->getDescription(),
            'amount' => $transaction->getAmount(),
            'category' => $transaction->getCategory()->getId(),
            'date' => $transaction->getDate()->format('m/d/Y g:i A')
        ];

        return $this->responseFormatter->asJson($response, $data);
    }

    public function update(Request $request, Response $response, Transaction $transaction): Response
    {
        $data = $this->requestValidatorFactory->make(TransactionRequestValidator::class)->validate(
            $request->getParsedBody()
        );

        $this->transactionsService->update($transaction, new TransactionData(
            description: $data['description'],
            amount: $data['amount'],
            date: new \DateTime($data['date']),
            category: $data['category']
        ));
        $this->entityManagerService->sync();

        return $response->withHeader('Location', '/transactions')->withStatus(302);
    }

    public function toggleReviewed(Response $response, Transaction $transaction): Response
    {
        $this->transactionsService->toggleReviewed($transaction);
        $this->entityManagerService->sync();

        return $response;
    }

}