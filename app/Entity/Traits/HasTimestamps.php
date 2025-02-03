<?php

namespace App\Entity\Traits;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\PrePersist;
use Doctrine\ORM\Mapping\PreUpdate;

trait HasTimestamps
{

    #[Column(name: 'created_at', type: Types::DATETIME_MUTABLE, options: ['default' => new \DateTime()])]
    private \DateTime $createdAt;

    #[Column(name: 'updated_at', type: Types::DATETIME_MUTABLE)]
    private \DateTime $updatedAt;

    #[PrePersist] #[PreUpdate]
    public function updateTimestamps() : void
    {
        if(! isset($this->createdAt)){
            $this->createdAt = new \DateTime();
        }

        $this->updatedAt = new \DateTime();
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

}