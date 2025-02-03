<?php

namespace App\Services;

use App\Contracts\EntityManagerServiceInterface;
use App\Contracts\UserInterface;
use App\DTO\DatatableQueryParams;
use App\Entity\Category;
use Doctrine\ORM\Tools\Pagination\Paginator;

class CategoryService
{

    public function __construct(
        private readonly EntityManagerServiceInterface $entityManager
    ){}

    public function create(string $name, UserInterface $user): Category
    {
        $category = new Category();

        $category->setUser($user);

        return $this->update($category, $name);
    }

    /** @return Category[] */
    public function getAll(): array
    {
        $repository = $this->entityManager->getRepository(Category::class);

        return $repository->findAll();
    }

    public function getPaginatedCategories(DatatableQueryParams $params): Paginator {
        $query = $this->entityManager->getRepository(Category::class)
            ->createQueryBuilder('c')
            ->setFirstResult($params->start)
            ->setMaxResults($params->length);

        $orderBy = in_array($params->orderBy, ['name', 'updatedAt', 'orderDir']) ? $params->orderBy : 'name';
        $orderDir = in_array($params->orderDir, ['asc', 'desc']) ? $params->orderDir : 'asc';

        if(! empty($params->searchTerm)){
            $query->where('c.name LIKE :searchTerm')->setParameter('searchTerm', '%' . addcslashes($params->searchTerm, '%_') . '%');
        }

        $query->orderBy('c.' . $orderBy, $orderDir);

        return new Paginator($query);
    }

    public function getById(int $id): ?Category
    {
        return $this->entityManager->find(Category::class, $id);
    }

    public function update(Category $category, string $name): Category
    {
        $category->setName($name);

        return $category;
    }

    public function getAllKeyedByName(): array
    {
        $ret = [];
        foreach($this->getAll() as $category){
            $ret[strtolower($category->getName())] = $category;
        }

        return $ret;
    }

    public function getTopSpendingCategories(int $limit): array
    {
        $query = $this->entityManager->createQuery('
            SELECT c.name, SUM(abs(t.amount)) AS total
            FROM App\Entity\Transaction t
            JOIN t.category c
            WHERE t.amount < 0
            GROUP BY c.id
            ORDER BY total DESC
        ');

        $query->setMaxResults($limit);

        return $query->getResult();
    }

}