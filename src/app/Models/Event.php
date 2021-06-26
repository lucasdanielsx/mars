<?php

namespace App\Models;

use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\UuidInterface;

class Event extends Model
{
    use HasFactory;

    /** @var UuidInterface */
    private $id;

    /** @var UuidInterface */
    private $fk_transaction_id;

    /** @var string */
    private $type;

    /** @var array */
    private $payload;

    /** @var string */
    private $messageId;

    /** @var DateTime */
    private $createdAt;

    /** @var DateTime */
    private $updatedAt;

    protected $fillable = [
        "id",
        "fk_transaction_id",
        "type",
        "payload",
        "updated_at",
        "created_at"
    ];

    protected $casts = [
        'id' => 'string',
        'fk_transaction_id' => 'string',
        'created_at' => 'datetime:Y-m-d',
        'updated_at' => 'datetime:Y-m-d'
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
    public function getFkTransactionId(): UuidInterface
    {
        return $this->fk_transaction_id;
    }

    /**
     * @param UuidInterface $fk_transaction_id
     */
    public function setFkTransactionId(UuidInterface $fk_transaction_id): void
    {
        $this->fk_transaction_id = $fk_transaction_id;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
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

    /**
     * @return string
     */
    public function getMessageId(): string
    {
        return $this->messageId;
    }

    /**
     * @param string $messageId
     */
    public function setMessageId(string $messageId): void
    {
        $this->messageId = $messageId;
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'fk_transaction_id');
    }
}
