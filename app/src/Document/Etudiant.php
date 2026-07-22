<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document(collection: 'etudiants')]
#[ODM\Index(keys: ['email' => 1], options: ['unique' => true])]
class Etudiant
{
    #[ODM\Id]
    private ?string $id = null;

    #[ODM\Field(type: 'string')]
    private string $nom = '';

    #[ODM\Field(type: 'string')]
    private string $prenom = '';

    #[ODM\Field(type: 'string')]
    private string $email = '';

    #[ODM\Field(type: 'string')]
    private string $niveau = '';

    #[ODM\Field(type: 'bool')]
    private bool $actif = true;

    #[ODM\ReferenceOne(targetDocument: Classe::class, storeAs: 'id', nullable: true)]
    private ?Classe $classe = null;

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

    public function getNiveau(): string
    {
        return $this->niveau;
    }

    public function setNiveau(string $niveau): self
    {
        $this->niveau = trim($niveau);

        return $this;
    }

    public function isActif(): bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): self
    {
        $this->actif = $actif;

        return $this;
    }

    public function getClasse(): ?Classe
    {
        return $this->classe;
    }

    public function setClasse(?Classe $classe): self
    {
        $this->classe = $classe;

        return $this;
    }
}