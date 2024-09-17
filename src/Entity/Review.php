<?php

// src/Entity/Review.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use JMS\Serializer\Annotation as Serializer;

#[ORM\Entity]
#[ORM\Table(name: "reviews")]
class Review
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private $id;

    #[ORM\Column(type: "integer")]
    private $rating;

    #[ORM\Column(type: "text", nullable: true)]
    private $comments;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private $createdAt;
    #[ORM\Column(type: "integer")]
    private $film;
    #[ORM\Column(type: "integer")]
    /**
     * @Serializer\Exclude()
     */
    private $user;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRating(): ?int
    {
        return $this->rating;
    }

    public function setRating(int $rating): self
    {
        $this->rating = $rating;

        return $this;
    }

    public function getComments(): ?string
    {
        return $this->comments;
    }

    public function setComments(?string $comments): self
    {
        $this->comments = $comments;

        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface  $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getFilm()
    {
        return $this->film;
    }

    public function setFilm(?int $film): self
    {
        $this->film = $film;

        return $this;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setUser(?int $user): self
    {
        $this->user = $user;

        return $this;
    }
}