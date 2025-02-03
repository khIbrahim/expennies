<?php
declare(strict_types=1);
namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;

#[Entity, Table(name: 'receipts')]
class Receipt
{

    #[Id, GeneratedValue, Column(options: ['unsigned' => true])]
    protected int $id;

    #[Column(name: 'file_name', type: 'string')]
    private string $fileName;

    #[Column(name: 'storage_filename', type: 'string')]
    private string $storageFilename;

    #[Column(name: 'media_type', type: 'string')]
    private string $mediaType;

    #[Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    private \DateTime $createdAt;

    #[ManyToOne(targetEntity: Transaction::class, inversedBy: 'receipts')]
    private Transaction $transaction;

    public function getId(): int
    {
        return $this->id;
    }

    public function getFilename(): string
    {
        return $this->fileName;
    }

    public function setFileName(string $fileName): Receipt
    {
        $this->fileName = $fileName;
        return $this;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): Receipt
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getTransaction(): Transaction
    {
        return $this->transaction;
    }

    public function setTransaction(Transaction $transaction): Receipt
    {
        $transaction->addReceipt($this);
        $this->transaction = $transaction;
        return $this;
    }

    public function getStorageFilename(): string
    {
        return $this->storageFilename;
    }

    public function setStorageFilename(string $storageFilename): Receipt
    {
        $this->storageFilename = $storageFilename;

        return $this;
    }

    public function getMediaType(): string
    {
        return $this->mediaType;
    }

    public function setMediaType(string $mediaType): Receipt
    {
        $this->mediaType = $mediaType;

        return $this;
    }

}