<?php

namespace App\Controllers;

use App\Contracts\RequestValidatorFactoryInterface;
use App\Entity\Receipt;
use App\Entity\Transaction;
use App\RequestValidators\UploadedReceiptValidator;
use App\Services\EntityManagerService;
use App\Services\ReceiptService;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\UploadedFileInterface;
use Slim\Psr7\Stream;

class ReceiptController
{

    public function __construct(
        private readonly Filesystem                       $fileSystem,
        private readonly RequestValidatorFactoryInterface $requestValidator,
        private readonly ReceiptService                   $receiptService,
        private readonly EntityManagerService             $entityManagerService
    ){}

    public function store(Request $request, Response $response, Transaction $transaction): Response
    {
        /** @var UploadedFileInterface $file */
        $file = $this->requestValidator->make(UploadedReceiptValidator::class)->validate(
            $request->getUploadedFiles()
        )['receipt'];

        $randomFileName = bin2hex(random_bytes(25));

        $this->fileSystem->write('receipts/' . $randomFileName, $file->getStream()->getContents());

        $this->entityManagerService->sync(
            $this->receiptService->create($transaction, $file->getClientFilename(), $randomFileName, $file->getClientMediaType())
        );

        return $response;
    }

    /**
     * @throws FilesystemException
     */
    public function download(Response $response, Receipt $receipt, Transaction $transaction): Response
    {
        if ($receipt->getTransaction()->getId() !== $transaction->getId()) {
            return $response->withStatus(401);
        }

        $file = $this->fileSystem->readStream('receipts/' . $receipt->getStorageFilename());

        $response = $response->withHeader(
            'Content-Disposition',
            'inline; filename="' . $receipt->getFilename() . '"'
        )->withHeader(
            'Content-Type',
            $receipt->getMediaType()
        );

        return $response->withBody(new Stream($file));
    }

    /**
     * @throws FilesystemException
     */
    public function delete(Response $response, Transaction $transaction, Receipt $receipt): Response
    {
        if ($receipt->getTransaction()->getId() !== $transaction->getId()) {
            return $response->withStatus(401);
        }

        $this->fileSystem->delete('receipts/' . $receipt->getStorageFilename());
        $this->entityManagerService->delete($receipt, true);

        return $response;
    }

}