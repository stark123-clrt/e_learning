<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

//#[ODM\Document]
#[ODM\Document(collection: 'formations')]
#[ODM\Index(keys: ['titre' => 1])]
#[ODM\Index(keys: ['categorie' => 1])]
class Formation
{
    #[ODM\Id]
    private ?string $id = null;

    #[ODM\Field(type: 'string')]
    private string $titre;

    #[ODM\Field(type: 'string')]
    private string $description;

    #[ODM\Field(type: 'string')]
    private string $categorie;

    #[ODM\Field(type: 'float')]
    private float $prix;

    #[ODM\Field(type: 'int')]
    private int $duree;

    #[ODM\Field(type: 'int')]
    private int $capaciteMax;

    #[ODM\ReferenceOne(targetDocument: Formateur::class, storeAs: 'id')]
    private ?Formateur $formateur = null;

    #[ODM\Field(type: 'date_immutable')]
    private \DateTimeImmutable $dateDebut;

    #[ODM\Field(type: 'date_immutable')]
    private \DateTimeImmutable $dateFin;

    #[ODM\Field(type: 'string')]
    private string $statut = 'OUVERTE';

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getTitre(): string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): self
    {
        $this->titre = trim($titre);
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = trim($description);
        return $this;
    }

    public function getCategorie(): string
    {
        return $this->categorie;
    }

    public function setCategorie(string $categorie): self
    {
        $this->categorie = trim($categorie);
        return $this;
    }

    public function getPrix(): float
    {
        return $this->prix;
    }

    public function setPrix(float $prix): self
    {
        $this->prix = $prix;
        return $this;
    }

    public function getDuree(): int
    {
        return $this->duree;
    }

    public function setDuree(int $duree): self
    {
        $this->duree = $duree;
        return $this;
    }

    public function getCapaciteMax(): int
    {
        return $this->capaciteMax;
    }

    public function setCapaciteMax(int $capaciteMax): self
    {
        $this->capaciteMax = $capaciteMax;
        return $this;
    }

    public function getFormateur(): ?Formateur
    {
        return $this->formateur;
    }

    public function setFormateur(?Formateur $formateur): self
    {
        $this->formateur = $formateur;
        return $this;
    }

    public function getDateDebut(): \DateTimeImmutable
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTimeImmutable $dateDebut): self
    {
        $this->dateDebut = $dateDebut;
        return $this;
    }

    public function getDateFin(): \DateTimeImmutable
    {
        return $this->dateFin;
    }

    public function setDateFin(\DateTimeImmutable $dateFin): self
    {
        $this->dateFin = $dateFin;
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