<?php

namespace App\Entity;

use App\Repository\TransfertRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TransfertRepository::class)]
class Transfert
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'transferts')]
    private ?User $client = null;

    #[ORM\Column(type: 'float')]
    private ?float $montant = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $frais = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $destinataire = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $tel = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $agency = null;

    #[ORM\ManyToOne(targetEntity: Agency::class, inversedBy: 'transferts')]
    private ?Agency $transagency = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $agent = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $transagent = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $facture = null;

    #[ORM\Column(type: 'bigint')]
    private ?int $secretid = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $sentAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $receveAt = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $destinateur = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $telsender = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $paid = null;

    #[ORM\Column(nullable: true)]
    private ?float $amountToPaid = null;

    #[ORM\Column]
    private ?float $taux = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $reference = null;

    #[ORM\Column(nullable: true)]
    private ?int $transacId = null;



    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClient(): ?User
    {
        return $this->client;
    }

    public function setClient(?User $client): self
    {
        $this->client = $client;

        return $this;
    }

    public function getMontant(): ?float
    {
        return $this->montant;
    }

    public function setMontant(float $montant): self
    {
        $this->montant = $montant;

        return $this;
    }

    public function getFrais(): ?float
    {
        return $this->frais;
    }

    public function setFrais(?float $frais): self
    {
        $this->frais = $frais;

        return $this;
    }

    public function getTel(): ?string
    {
        return $this->tel;
    }

    public function setTel(string $tel): self
    {
        $this->tel = $tel;

        return $this;
    }

    public function getAgency(): ?string
    {
        return $this->agency;
    }

    public function setAgency(string $agency): self
    {
        $this->agency = $agency;

        return $this;
    }

    public function getTransagency(): ?Agency
    {
        return $this->transagency;
    }

    public function setTransagency(?Agency $transagency): self
    {
        $this->transagency = $transagency;

        return $this;
    }

    public function getAgent(): ?string
    {
        return $this->agent;
    }

    public function setAgent(string $agent): self
    {
        $this->agent = $agent;

        return $this;
    }

    public function getTransagent(): ?string
    {
        return $this->transagent;
    }

    public function setTransagent(string $transagent): self
    {
        $this->transagent = $transagent;

        return $this;
    }

    public function getFacture(): ?string
    {
        return $this->facture;
    }

    public function setFacture(?string $facture): self
    {
        $this->facture = $facture;

        return $this;
    }

    public function getDestinataire(): ?string
    {
        return $this->destinataire;
    }

    public function setDestinataire(string $destinataire): self
    {
        $this->destinataire = $destinataire;

        return $this;
    }


    public function getSecretid(): ?int
    {
        return $this->secretid;
    }

    public function setSecretid(int $secretid): self
    {
        $this->secretid = $secretid;

        return $this;
    }

    public function getSentAt(): ?\DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function setSentAt(\DateTimeImmutable $sentAt): self
    {
        $this->sentAt = $sentAt;

        return $this;
    }

    public function getReceveAt(): ?\DateTimeImmutable
    {
        return $this->receveAt;
    }

    public function setReceveAt(?\DateTimeImmutable $receveAt): self
    {
        $this->receveAt = $receveAt;

        return $this;
    }

//    public function __toString()
//    {
//        return $this->getSecretid();
//    }

    public function getDestinateur(): ?string
    {
        return $this->destinateur;
    }

    public function setDestinateur(?string $destinateur): self
    {
        $this->destinateur = $destinateur;

        return $this;
    }

    public function getTelsender(): ?string
    {
        return $this->telsender;
    }

    public function setTelsender(?string $telsender): self
    {
        $this->telsender = $telsender;

        return $this;
    }

    public function getPaid(): ?string
    {
        return $this->paid;
    }

    public function setPaid(?string $paid): self
    {
        $this->paid = $paid;

        return $this;
    }

    public function getAmountToPaid(): ?float
    {
        return $this->amountToPaid;
    }

    public function setAmountToPaid(?float $amountToPaid): static
    {
        $this->amountToPaid = $amountToPaid;

        return $this;
    }

    public function getTaux(): ?float
    {
        return $this->taux;
    }

    public function setTaux(float $taux): static
    {
        $this->taux = $taux;

        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }

    public function getTransacId(): ?int
    {
        return $this->transacId;
    }

    public function setTransacId(?int $transacId): static
    {
        $this->transacId = $transacId;

        return $this;
    }
}
