<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

//#[ODM\Document]
#[ODM\Document(collection: 'inscriptions')]
#[ODM\Index(keys: ['etudiant' => 1])]
#[ODM\Index(keys: ['formation' => 1])]
#[ODM\Index(
    keys: ['etudiant' => 1, 'formation' => 1],
    options: ['unique' => true]
)]
class Inscription
{
    #[ODM\Id]
    private ?string $id = null;

    #[ODM\ReferenceOne(targetDocument: Etudiant::class, storeAs: 'id')]
    private ?Etudiant $etudiant = null;

    #[ODM\ReferenceOne(targetDocument: Formation::class, storeAs: 'id')]
    private ?Formation $formation = null;

    #[ODM\Field(type: 'date_immutable')]
    private \DateTimeImmutable $dateInscription;

    #[ODM\Field(type: 'string')]
    private string $statut = 'ACTIVE';

    #[ODM\Field(type: 'float', nullable: true)]
    private ?float $note = null;

    public function __construct()
    {
        $this->dateInscription = new \DateTimeImmutable();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getEtudiant(): ?Etudiant
    {
        return $this->etudiant;
    }

    public function setEtudiant(?Etudiant $etudiant): self
    {
        $this->etudiant = $etudiant;
        return $this;
    }

    public function getFormation(): ?Formation
    {
        return $this->formation;
    }

    public function setFormation(?Formation $formation): self
    {
        $this->formation = $formation;
        return $this;
    }

    public function getDateInscription(): \DateTimeImmutable
    {
        return $this->dateInscription;
    }

    public function setDateInscription(\DateTimeImmutable $dateInscription): self
    {
        $this->dateInscription = $dateInscription;
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

    public function getNote(): ?float
    {
        return $this->note;
    }

    public function setNote(?float $note): self
    {
        if ($note !== null && ($note < 0 || $note > 20)) {
            throw new \InvalidArgumentException(
                'La note doit être comprise entre 0 et 20.'
            );
        }

        $this->note = $note;
        return $this;
    }
}