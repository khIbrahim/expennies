<?php

namespace App\Services;

use App\Attributes\FromCache;
use App\Contracts\EntityManagerServiceInterface;
use App\DTO\DatatableQueryParams;
use App\DTO\TransactionData;
use App\Entity\Transaction;
use App\Entity\User;
use Doctrine\ORM\Tools\Pagination\Paginator;

class TransactionsService
{

    public function __construct(
        private readonly EntityManagerServiceInterface $entityManager,
    ){}

    #[FromCache('transaction')]
    public function getAll(): array
    {
        return $this->entityManager->getRepository(Transaction::class)->findAll();
    }

    public function getPaginated(DatatableQueryParams $params): Paginator
    {
        $query = $this->entityManager
            ->getRepository(Transaction::class)
            ->createQueryBuilder('t')
            ->select('t', 'c', 'r')
            ->leftJoin('t.category', 'c')
            ->leftJoin('t.receipts', 'r')
            ->setFirstResult($params->start)
            ->setMaxResults($params->length);

        $orderBy = in_array($params->orderBy, ['name', 'date', 'category', 'description']) ? $params->orderBy : 'date';
        $orderDir = in_array($params->orderDir, ['asc', 'desc']) ? $params->orderDir : 'desc';

        if(! empty($params->searchTerm)){
            $query->where('t.description LIKE :description')
                ->setParameter('description', '%'.addcslashes($params->searchTerm, "%_").'%');
        }

        if($orderBy === 'category'){
            $query->orderBy('c.name', $orderDir);
        } else {
            $query->orderBy('t.' . $orderBy, $orderDir);
        }

        return new Paginator($query);
    }

    public function create(TransactionData $transactionData, User $user): Transaction
    {
        $transaction = new Transaction();

        $transaction->setUser($user);

        return $this->update($transaction, $transactionData);
    }

    #[FromCache('transaction', 'id')]
    public function getById(int $id): ?Transaction
    {
        return $this->entityManager->find(Transaction::class, $id);
    }

    public function update(Transaction $transaction, TransactionData $transactionData): Transaction
    {
        $transaction->setDescription($transactionData->description);
        $transaction->setAmount($transactionData->amount);
        $transaction->setDate($transactionData->date);
        $transaction->setCategory($transactionData->category);
        $transaction->setWasReviewed(false);

        return $transaction;
    }

    public function toggleReviewed(Transaction $transaction): void
    {
        $transaction->setWasReviewed(! $transaction->wasReviewed());
    }

    public function getRecentTransactions(int $limit): array
    {
        return $this->entityManager->getRepository(Transaction::class)
            ->createQueryBuilder('t')
            ->leftJoin('t.category', 'c')
            ->select('t', 'c')
            ->orderBy('t.date', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }

    public function getSummary(): array
    {
        $query = $this->entityManager->createQuery('
            SELECT SUM(CASE WHEN t.amount > 0 THEN t.amount ELSE 0 END) AS income,
                   SUM(CASE WHEN t.amount < 0 THEN ABS(t.amount) ELSE 0 END) AS expense,
                   SUM(t.amount) AS net
            FROM App\Entity\Transaction t
        ');

        return $query->getArrayResult();
    }

    public function getMonthlySummary(int $year = 2025): array
    {
        $conn = $this->entityManager->getConnection();
        $sql = "
            SELECT 
                SUM(CASE WHEN t.amount > 0 THEN t.amount ELSE 0 END) AS income,
                SUM(CASE WHEN t.amount < 0 THEN ABS(t.amount) ELSE 0 END) AS expense,
                MONTH(t.date) AS month
            FROM transactions t
            WHERE YEAR(t.date) = :year
            GROUP BY month
            ORDER BY month ASC
        ";

        $stmt = $conn->prepare($sql);
        return $stmt->executeQuery(['year' => $year])->fetchAllAssociative();
    }

}