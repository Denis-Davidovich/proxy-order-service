<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: 'orders')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: false)]
    private string $name;

    #[ORM\Column(type: Types::STRING, name: 'client_name', length: 255, nullable: true)]
    private ?string $clientName = null;

    #[ORM\Column(type: Types::INTEGER, nullable: false, options: ['default' => 1])]
    private int $status = 1;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, name: 'create_date', nullable: false)]
    private \DateTimeInterface $createDate;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, name: 'update_date', nullable: true)]
    private ?\DateTimeInterface $updateDate = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: false)]
    private string $hash;

    #[ORM\Column(type: Types::STRING, length: 64, nullable: false)]
    private string $token;

    #[ORM\Column(type: Types::STRING, length: 1000, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::SMALLINT, name: 'pay_type', nullable: false)]
    private int $payType = 1;

    #[ORM\Column(type: Types::STRING, length: 5, nullable: false)]
    private string $locale = 'ru';

    public function __construct()
    {
        $this->createDate = new \DateTime();
        $this->hash = md5(uniqid((string)mt_rand(), true));
        $this->token = bin2hex(random_bytes(32));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getClientName(): ?string
    {
        return $this->clientName;
    }

    public function setClientName(?string $clientName): self
    {
        $this->clientName = $clientName;
        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getCreateDate(): \DateTimeInterface
    {
        return $this->createDate;
    }

    public function getUpdateDate(): ?\DateTimeInterface
    {
        return $this->updateDate;
    }

    public function setUpdateDate(?\DateTimeInterface $updateDate): self
    {
        $this->updateDate = $updateDate;
        return $this;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getPayType(): int
    {
        return $this->payType;
    }

    public function setPayType(int $payType): self
    {
        $this->payType = $payType;
        return $this;
    }
}
