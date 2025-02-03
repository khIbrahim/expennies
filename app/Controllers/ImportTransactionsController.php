<?php

namespace App\Controllers;

use App\Contracts\RequestValidatorFactoryInterface;
use App\Contracts\RequestValidatorInterface;
use App\Entity\User;
use App\RequestValidators\CsvUploadRequestValidator;
use App\Services\TransactionImportService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\UploadedFileInterface;

class ImportTransactionsController
{

    public function __construct(
        private readonly TransactionImportService         $transactionImportService,
        private readonly RequestValidatorFactoryInterface $requestValidatorFactory
    ){}

    public function import(Request $request, Response $response): Response
    {
        /** @var UploadedFileInterface $files */
        $file = $this->requestValidatorFactory->make(CsvUploadRequestValidator::class)->validate(
            $request->getUploadedFiles()
        )['csvFile'];
        /** @var User $user */
        $user = $request->getAttribute('user');

        $this->transactionImportService->importFromFile($file, $user);

        return $response;
    }

}