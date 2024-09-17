<?php

// src/Entity/Film.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "films")]
class Film
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private $id;

    #[ORM\Column(type: "string")]
    private $name;

    #[ORM\Column(type: "text")]
    private $description;

    #[ORM\Column(type: "string", nullable: true)]
    private $imagePath;

    #[ORM\Column(type: "string", nullable: true)]
    private $director;

    #[ORM\Column(type: "integer", nullable: true)]
    private $runningTime;

    #[ORM\Column(type: "text", nullable: true)]
    private $cast;
    #[ORM\Column(type: "boolean", options: ["default" => false])]
    private $approved;
    #[ORM\OneToMany(mappedBy: "film", targetEntity: "Review")]
    private $reviews;

    // Getters and Setters...

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getImagePath(): ?string
    {
        return $this->imagePath;
    }

    public function setImagePath(?string $imagePath): self
    {
        $this->imagePath = $imagePath;

        return $this;
    }

    public function getDirector(): ?string
    {
        return $this->director;
    }

    public function setDirector(?string $director): self
    {
        $this->director = $director;

        return $this;
    }

    public function getRunningTime(): ?int
    {
        return $this->runningTime;
    }

    public function setRunningTime(?int $runningTime): self
    {
        $this->runningTime = $runningTime;

        return $this;
    }

    public function getCast(): ?string
    {
        return $this->cast;
    }

    public function setCast(?string $cast): self
    {
        $this->cast = $cast;

        return $this;
    }

    public function getReviews(): ?array
    {
        return $this->reviews->toArray();
    }
    public function getApproved(): ?bool
    {
        return $this->approved;
    }

    public function setApproved(bool $approved): self
    {
        $this->approved = $approved;

        return $this;
    }
}
