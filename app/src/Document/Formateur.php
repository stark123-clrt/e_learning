<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

//#[ODM\Document]
#[ODM\Document(collection: 'formateurs')]
#[ODM\Index(keys: ['email' => 1], options: ['unique' => true])]
class Formateur
{
    #[ODM\Id]
    private ?string $id = null;

    #[ODM\Field(type: 'string')]
    private string $nom;

    #[ODM\Field(type: 'string')]
    private string $prenom;

    #[ODM\Field(type: 'string')]
    private string $email;

    #[ODM\Field(type: 'string')]
    private string $specialite;

    #[ODM\Field(type: 'string')]
    private string $statut = 'ACTIF';

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = trim($nom);

        return $this;
    }

    public function getPrenom(): string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): self
    {
        $this->prenom = trim($prenom);

        return $this;
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

    public function getSpecialite(): string
    {
        return $this->specialite;
    }

    public function setSpecialite(string $specialite): self
    {
        $this->specialite = trim($specialite);

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
}