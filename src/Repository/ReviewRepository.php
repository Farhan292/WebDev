<?php
// src/Repository/ReviewRepository.php

namespace App\Repository;

use App\Entity\Review;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Review::class);
    }
    public function updateReview($id, $comments, $created_at)
    {
        $entityManager = $this->getEntityManager();
        $conn = $entityManager->getConnection();

        $sqlQuery = 'UPDATE reviews SET comments = :comments, created_at = :created_at WHERE id = :id';

        $params = [
            'id' => $id,
            'comments' => $comments,
            'created_at' => $created_at,
        ];

        $conn->executeUpdate($sqlQuery, $params);
    }
    public function findCommentsById($id): ?Review
    {
        $entityManager = $this->getEntityManager();
        $conn = $entityManager->getConnection();

        $sqlQuery = 'SELECT comments FROM reviews WHERE id = :id';
        $statement = $conn->executeQuery($sqlQuery, ['id' => $id]);

        $row = $statement->fetchAssociative();

        if (!$row) {
            return null; // Return null if no message is found for the given ID
        }

        $review = new Review();
        $review->setComments($row['comments']);

        return $review;
    }
}