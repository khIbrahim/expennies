<?php
declare(strict_types=1);
namespace App\Entity;

use App\Contracts\OwnableInterface;
use App\Entity\Traits\HasTimestamps;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\Table;

#[Entity, Table(name: 'transactions')]
#[HasLifecycleCallbacks]
class Transaction implements OwnableInterface
{
    use HasTimestamps;

    #[Id, GeneratedValue, Column(options: ['unsigned' => true])]
    protected int $id;

    #[Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $wasReviewed;

    #[Column(type: 'string', length: 255)]
    private string $description;

    #[Column(type: Types::DECIMAL, precision: 13, scale: 3)]
    private float $amount;

    #[Column(type: Types::DATETIME_MUTABLE)]
    private \DateTime $date;

    #[ManyToOne(targetEntity: Category::class, inversedBy: 'transactions')]
    private Category $category;

    #[ManyToOne(targetEntity: User::class, inversedBy: 'transactions')]
    private User $user;

    #[OneToMany(mappedBy: 'transaction', targetEntity: Receipt::class, cascade: ['remove'])]
    private Collection $receipts;

    public function __construct()
    {
        $this->receipts = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): Transaction
    {
        $this->id = $id;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): Transaction
    {
        $this->description = $description;
        return $this;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): Transaction
    {
        $this->amount = $amount;
        return $this;
    }

    public function getDate(): \DateTime
    {
        return $this->date;
    }

    public function setDate(\DateTime $date): Transaction
    {
        $this->date = $date;
        return $this;
    }

    public function getCategory(): Category
    {
        return $this->category;
    }

    public function setCategory(Category $category): Transaction
    {
        $category->addTransaction($this);

        $this->category = $category;
        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): Transaction
    {
        $user->addTransaction($this);

        $this->user = $user;
        return $this;
    }

    public function getReceipts(): Collection
    {
        return $this->receipts;
    }

    public function addReceipt(Receipt $receipt): Transaction
    {
        $this->receipts->add($receipt);

        return $this;
    }

    public function wasReviewed(): bool
    {
        return $this->wasReviewed;
    }

    public function setWasReviewed(bool $wasReviewed): Transaction
    {
        $this->wasReviewed = $wasReviewed;

        return $this;
    }

}