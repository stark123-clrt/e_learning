<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

//#[ODM\Document]
#[ODM\Document(collection: 'utilisateurs')]
#[ODM\Index(keys: ['email' => 1], options: ['unique' => true])]
class Utilisateur
{
    #[ODM\Id]
    private ?string $id = null;

    #[ODM\Field(type: 'string')]
    private string $email;

    #[ODM\Field(type: 'string')]
    private string $motDePasse;

    #[ODM\Field(type: 'string')]
    private string $role;

    #[ODM\Field(type: 'string')]
    private string $statut = 'ACTIF';

    #[ODM\Field(type: 'date_immutable')]
    private \DateTimeImmutable $dateCreation;

    #[ODM\Field(type: 'string', nullable: true)]
    private ?string $profilId = null;

    public function __construct()
    {
        $this->dateCreation = new \DateTimeImmutable();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = mb_strtolower(trim($email));
        return $this;
    }

    public function getMotDePasse(): string
    {
        return $this->motDePasse;
    }

    public function setMotDePasse(string $motDePasse): self
    {
        $this->motDePasse = $motDePasse;
        return $this;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): self
    {
        $this->role = $role;
        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): self
    {
        $this->statut = $statut;
        return $this;
    }

    public function getDateCreation(): \DateTimeImmutable
    {
        return $this->dateCreation;
    }

    public function getProfilId(): ?string
    {
        return $this->profilId;
    }

    public function setProfilId(?string $profilId): self
    {
        $this->profilId = $profilId;
        return $this;
    }
}