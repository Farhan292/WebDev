<?php

namespace App\Repository;

use App\Entity\Announcement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use http\Env\Response;

/**
 * @extends ServiceEntityRepository<Announcement>
 *
 * @method Announcement|null find($id, $lockMode = null, $lockVersion = null)
 * @method Announcement|null findOneBy(array $criteria, array $orderBy = null)
 * @method Announcement[]    findAll()
 * @method Announcement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AnnouncementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Announcement::class);
    }
    public function findMessageById($id): ?Announcement
    {
        $entityManager = $this->getEntityManager();
        $conn = $entityManager->getConnection();

        $sqlQuery = 'SELECT message FROM announcement WHERE id = :id';
        $statement = $conn->executeQuery($sqlQuery, ['id' => $id]);

        $row = $statement->fetchAssociative();

        if (!$row) {
            return null; // Return null if no message is found for the given ID
        }

        $announcement = new Announcement();
        $announcement->setMessage($row['message']);

        return $announcement;
    }
    public function showAllMessages()
    {
        $entityManager = $this->getEntityManager();
        $conn = $entityManager->getConnection();

        $sqlQuery = 'SELECT message FROM announcement';
        $statement = $conn->executeQuery($sqlQuery);

        $dataSet = [];
        while ($row = $statement->fetchAssociative()) {
            $announcement = new Announcement();
            $announcement->setMessage($row['message']);
            $dataSet[] = $announcement;
        }

        return $dataSet;
    }
    public function updateAnnouncement($id, $message, $timestamp)
    {
        $entityManager = $this->getEntityManager();
        $conn = $entityManager->getConnection();

        $sqlQuery = 'UPDATE announcement SET message = :message, timestamp = :timestamp WHERE id = :id';

        $params = [
            'id' => $id,
            'message' => $message,
            'timestamp' => $timestamp,
        ];

        $conn->executeUpdate($sqlQuery, $params);
    }

//    /**
//     * @return Announcement[] Returns an array of Announcement objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('a.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Announcement
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
