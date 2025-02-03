<?php
declare(strict_types=1);
namespace App\Entity;

use App\Contracts\OwnableInterface;
use App\Entity\Traits\HasTimestamps;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\Table;

#[Entity, Table('categories')]
#[HasLifecycleCallbacks]
class Category implements OwnableInterface
{
    use HasTimestamps;

    #[Id, GeneratedValue, Column(options: ['unsigned' => true])]
    protected int $id;

    #[Column(type: 'string', length: 255)]
    private string $name;

    #[OneToMany(mappedBy: 'category', targetEntity: Transaction::class)]
    private Collection $transactions;

    #[ManyToOne(targetEntity: User::class, inversedBy: 'categories')]
    private User $user;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): Category
    {
        $this->id = $id;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Category
    {
        $this->name = $name;
        return $this;
    }

    public function addTransaction(Transaction $transaction): Category
    {
        $this->transactions->add($transaction);

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): Category
    {
        //$user->addCategory($this);

        $this->user = $user;

        return $this;
    }

}