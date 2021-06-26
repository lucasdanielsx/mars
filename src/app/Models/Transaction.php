<?php

namespace App\Models;

use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\UuidInterface;

class Transaction extends Model
{
    use HasFactory;

    /** @var UuidInterface */
    private $id;

    /** @var UuidInterface */
    private $fk_wallet_from;

    /** @var UuidInterface */
    private $fk_wallet_to;

    /** @var int */
    private $amount;

    /** @var string */
    private $status;

    /** @var array */
    private $payload;

    /** @var DateTime */
    private $createdAt;

    /** @var DateTime */
    private $updatedAt;

    protected $fillable = [
        "id",
        "fk_wallet_from",
        "fk_wallet_to",
        "amount",
        "status",
        "payload",
        "updated_at",
        "created_at"
    ];

    protected $casts = [
        'id' => 'string',
        'fk_wallet_from' => 'string',
        'fk_wallet_to' => 'string'
    ];

    /**
     * @return UuidInterface
     */
    public function getId(): UuidInterface
    {
        return $this->id;
    }

    /**
     * @param UuidInterface $id
     */
    public function setId(UuidInterface $id): void
    {
        $this->id = $id;
    }

    /**
     * @return UuidInterface
     */
    public function getFkWalletFrom(): UuidInterface
    {
        return $this->fk_wallet_from;
    }

    /**
     * @param UuidInterface $fk_wallet_from
     */
    public function setFkWalletFrom(UuidInterface $fk_wallet_from): void
    {
        $this->fk_wallet_from = $fk_wallet_from;
    }

    /**
     * @return UuidInterface
     */
    public function getFkWalletTo(): UuidInterface
    {
        return $this->fk_wallet_to;
    }

    /**
     * @param UuidInterface $fk_wallet_to
     */
    public function setFkWalletTo(UuidInterface $fk_wallet_to): void
    {
        $this->fk_wallet_to = $fk_wallet_to;
    }

    /**
     * @return int
     */
    public function getAmount(): int
    {
        return $this->amount;
    }

    /**
     * @param int $amount
     */
    public function setAmount(int $amount): void
    {
        $this->amount = $amount;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    /**
     * @return array
     */
    public function getPayload(): array
    {
        return $this->payload;
    }

    /**
     * @param array $payload
     */
    public function setPayload(array $payload): void
    {
        $this->payload = $payload;
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param DateTime $createdAt
     */
    public function setCreatedAt(DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return DateTime
     */
    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    /**
     * @param DateTime $updatedAt
     */
    public function setUpdatedAt(DateTime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }
}
