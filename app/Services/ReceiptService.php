<?php

namespace App\Services;

use App\Contracts\EntityManagerServiceInterface;
use App\Entity\Receipt;
use App\Entity\Transaction;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;

class ReceiptService
{

    public function __construct(
        private readonly EntityManagerServiceInterface $entityManagerService
    ){}

    public function create(
        Transaction $transaction,
        string      $filename,
        string      $storageFilename,
        string      $mediaType
    ): Receipt
    {
        $receipt = new Receipt();

        $receipt->setTransaction($transaction);
        $receipt->setFileName($filename);
        $receipt->setStorageFilename($storageFilename);
        $receipt->setMediaType($mediaType);
        $receipt->setCreatedAt(new \DateTime());

        return $receipt;
    }

    public function getById(int $id): ?Receipt
    {
        return $this->entityManagerService->find(Receipt::class, $id);
    }
}