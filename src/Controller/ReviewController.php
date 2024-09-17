<?php

namespace App\Controller;

use App\Entity\Film;
use App\Entity\Review;
use App\Entity\User;
use App\Form\Type\ReviewType;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
class ReviewController extends AbstractController
{
    //Route for editing review
    #[Route('/editReview/{id}', name: 'editReview')]
    public function editReview($id, Request $request, EntityManagerInterface $entityManager): Response
    {
        //Get existing review
        $reviewRepository = $entityManager->getRepository(Review::class);
        $existingReview = $reviewRepository->find($id);
        if (!$existingReview) {
            $this->addFlash('error', 'Review not found');
            return $this->redirectToRoute('myReviews');
        }


        //Get film for review
        $filmId = $existingReview->getFilm();

        $form = $this->createForm(ReviewType::class, $existingReview);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //Update review and refreshes
            $existingReview->setFilm($filmId);
            $currentDateTime = new \DateTime();
            $existingReview->setCreatedAt($currentDateTime);
            $entityManager->flush();
            return $this->redirectToRoute('myReviews');
        }
        //Grabbing reviews
        $userId = $this->getUser()->getId();
        $reviews = $entityManager
            ->getRepository(Review::class)
            ->findBy(['user' => $userId]);
        //Getting films for revies
        $filmArray = [];
        $index = 0;
        foreach ($reviews as $userReview) {
            $filmId = $userReview->getFilm();
            $film = $entityManager->getRepository(Film::class)->find($filmId);

            $filmArray[$index] = $film;
            $index++;
        }
        return $this->render('myReviews.html.twig', [
            'review' => $existingReview,
            'form1' => $form->createView(),
            'reviews' => $reviews,
            'films' => $filmArray,
        ]);
    }
    //Route for removing review
    #[Route('/deleteReview/{id}', name: 'deleteReview')]
    public function deleteReview($id, EntityManagerInterface $entityManager): Response
    {
        $reviewRepository = $entityManager->getRepository(Review::class);
        $review = $reviewRepository->find($id);
        //Remove and save database
        $entityManager->remove($review);
        $entityManager->flush();

        return $this->redirectToRoute('myReviews');
    }
}