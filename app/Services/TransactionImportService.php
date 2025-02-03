<?php

namespace App\Services;

use App\DTO\TransactionData;
use App\Entity\Transaction;
use App\Entity\User;
use Psr\Http\Message\UploadedFileInterface;

class TransactionImportService
{

    public function __construct(
        private readonly CategoryService      $categoryService,
        private readonly TransactionsService  $transactionsService,
        private readonly EntityManagerService $entityManagerService,
    ){}

    public function importFromFile(UploadedFileInterface $file, User $user): void
    {
        $resource   = fopen($file->getStream()->getMetadata('uri'), 'r');
        $categories = $this->categoryService->getAllKeyedByName();

        fgetcsv($resource);

        $count = 1;
        $batchSize = 250;
        while(($row = fgetcsv($resource)) !== false) {
            [$description, $amount, $category, $date] = $row;

            $date = new \DateTime($date);
            $category = $categories[strtolower((string) $category)] ?? null;
            if(! $category){
                continue;
            }
            $amount = (float) str_replace([",", ".", " ", "_", "$"], "", $amount);

            $transactionData = new TransactionData((string) $description, $amount, $date, $category);
            $this->entityManagerService->persist(
                $this->transactionsService->create($transactionData, $user)
            );

            if($count % $batchSize === 0) {
                $this->entityManagerService->sync();
                $this->entityManagerService->clear(Transaction::class);

                $count = 1;
            } else {
                $count++;
            }

            if($count > 1){
                $this->entityManagerService->sync();
                $this->entityManagerService->clear(Transaction::class);
            }
        }

    }


}