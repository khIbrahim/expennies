<?php

namespace App\RequestValidators;

use App\Contracts\RequestValidatorInterface;
use App\Exception\ValidationException;
use App\Services\ReceiptService;
use App\Services\TransactionsService;

class DownloadReceiptRequestValidator implements RequestValidatorInterface
{

    public function __construct(
        private readonly ReceiptService      $receiptService,
        private readonly TransactionsService $transactionsService
    ){}

    public function validate(array $data): array
    {
        $transactionId = (int) $data['transactionId'];
        $receiptId     = (int) $data['id'];

        if(! $transactionId || ! $receiptId) {
            throw new ValidationException(['receipt' => "Impossible de trouver le receipt identifiant", 'transaction' => "Impossible de trouver le transaction identifiant"]);
        }

        if(! ($receipt = $this->receiptService->getById($receiptId)) || ! ($transaction = $this->transactionsService->getById($transactionId))) {
            throw new ValidationException(['receipt' => "Impossible de trouver le receipt identifiant", 'transaction' => "Impossible de trouver le transaction identifiant"]);
        }

        if($receipt->getTransaction()->getId() !== $transactionId){
            throw new ValidationException(['receipt' => "l'id de la transaction dans le receipt n'est pas égal à celui de la request"]);
        }

        $data['receipt'] = $receipt;
        $data['transaction'] = $transaction;

        return $data;
    }
}