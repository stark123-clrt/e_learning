<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document(collection: 'progressions')]
#[ODM\Index(keys: ['etudiant' => 1, 'cours' => 1], options: ['unique' => true])]
class Progression
{
    #[ODM\Id]
    private ?string $id = null;

    #[ODM\ReferenceOne(targetDocument: Etudiant::class, storeAs: 'id')]
    private ?Etudiant $etudiant = null;

    #[ODM\ReferenceOne(targetDocument: Cours::class, storeAs: 'id')]
    private ?Cours $cours = null;

    #[ODM\Field(type: 'date_immutable')]
    private \DateTimeImmutable $dateValidation;

    public function __construct()
    {
        $this->dateValidation = new \DateTimeImmutable();
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

    public function getCours(): ?Cours
    {
        return $this->cours;
    }

    public function setCours(?Cours $cours): self
    {
        $this->cours = $cours;
        return $this;
    }

    public function getDateValidation(): \DateTimeImmutable
    {
        return $this->dateValidation;
    }
}
