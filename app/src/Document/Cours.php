<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document(collection: 'cours')]
#[ODM\Index(keys: ['formation' => 1])]
class Cours
{
    #[ODM\Id]
    private ?string $id = null;

    #[ODM\Field(type: 'string')]
    private string $titre = '';

    #[ODM\Field(type: 'string')]
    private string $description = '';

    #[ODM\Field(type: 'string')]
    private string $videoUrl = '';

    #[ODM\Field(type: 'int')]
    private int $ordre = 1;

    #[ODM\ReferenceOne(targetDocument: Formation::class, storeAs: 'id')]
    private ?Formation $formation = null;

    #[ODM\Field(type: 'date_immutable')]
    private \DateTimeImmutable $dateCreation;

    public function __construct()
    {
        $this->dateCreation = new \DateTimeImmutable();
    }

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

    public function getVideoUrl(): string
    {
        return $this->videoUrl;
    }

    public function setVideoUrl(string $videoUrl): self
    {
        $this->videoUrl = trim($videoUrl);
        return $this;
    }

    public function getOrdre(): int
    {
        return $this->ordre;
    }

    public function setOrdre(int $ordre): self
    {
        $this->ordre = $ordre;
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

    public function getDateCreation(): \DateTimeImmutable
    {
        return $this->dateCreation;
    }
}
