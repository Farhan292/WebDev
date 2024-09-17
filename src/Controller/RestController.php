<?php
namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use App\Repository\ReviewRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\Review;
use App\Form\Type\ReviewType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


class RestController extends AbstractFOSRestController
{
    #[Rest\Get('/api/v1/reviews', name: 'review_list')]
    public function getReviews(Request $request, ReviewRepository $reviewRepository, UserRepository $userRepository, UserPasswordHasherInterface $userPasswordHasher)
    {
        if (!$this->isAuthorised($userRepository, $request, $userPasswordHasher)) {
            return new JsonResponse(['error' => 'UNAUTHORIZED', 'status_code' => Response::HTTP_UNAUTHORIZED], Response::HTTP_UNAUTHORIZED);
        }
        $reviews = $reviewRepository->findAll();
        return $this->handleView($this->view($reviews, Response::HTTP_OK));
    }
    #[Rest\Get('/api/v1/reviews/{userId}', name: 'reviews_by_user')]
    public function getReviewsByUser(Request $request, ReviewRepository $reviewRepository, UserRepository $userRepository, UserPasswordHasherInterface $userPasswordHasher)
    {
        if (!$this->isAuthorised($userRepository, $request, $userPasswordHasher)) {
            return new JsonResponse(['error' => 'UNAUTHORIZED', 'status_code' => Response::HTTP_UNAUTHORIZED], Response::HTTP_UNAUTHORIZED);
        }
        $userId = $request->attributes->get('userId');
        $reviews = $reviewRepository->findBy(['user' => $userId]);

        if (!$reviews) {
            return $this->handleView($this->view(['message' => 'No reviews found for the specified user ID', 'Status code' => Response::HTTP_NOT_FOUND], Response::HTTP_NOT_FOUND));
        }

        return $this->handleView($this->view($reviews, Response::HTTP_OK));
    }

    #[Rest\Post('/api/v1/newReview', methods: ['POST'])]
    public function createReview(Request $request, EntityManagerInterface $entityManager, UserRepository $userRepository, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        if (!$this->isAuthorised($userRepository, $request, $userPasswordHasher)) {
            return new JsonResponse(['error' => 'UNAUTHORIZED', 'status_code' => Response::HTTP_UNAUTHORIZED], Response::HTTP_UNAUTHORIZED);
        }
        $data = json_decode($request->getContent(), true);

        if (!isset($data['film']) || !isset($data['rating']) || !isset($data['comments']) || !isset($data['user']))
        {
            return new JsonResponse(['error' => 'Required fields are missing', 'Status code' => Response::HTTP_BAD_REQUEST], Response::HTTP_BAD_REQUEST);
        }

        if (is_numeric($data['film']) && is_numeric($data['rating']) && is_numeric($data['user']) && is_string($data['comments'])) {
            $review = new Review();
            $review->setUser($data['user']);
            $review->setFilm($data['film']);
            $review->setRating($data['rating']);
            $review->setComments($data['comments']);

            $currentDateTime = new \DateTime();
            $review->setCreatedAt($currentDateTime);

            $form = $this->createForm(ReviewType::class, $review, ['csrf_protection' => false]);
            $form->submit($data);

            $entityManager->persist($review);
            $entityManager->flush();

            return new JsonResponse(['message' => 'Review created', 'id' => $review->getId(), 'Status code' => Response::HTTP_CREATED], Response::HTTP_CREATED);
        }
        else {
            return new JsonResponse(['error' => 'Invalid data - One or more of the fields are invalid', 'Status code' => Response::HTTP_BAD_REQUEST], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Rest\Put('/api/v1/updateReview/{id}', name: 'edit_review')]
    public function updateReview(Request $request, EntityManagerInterface $entityManager, int $id, UserRepository $userRepository, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        if (!$this->isAuthorised($userRepository, $request, $userPasswordHasher)) {
            return new JsonResponse(['error' => 'UNAUTHORIZED', 'status_code' => Response::HTTP_UNAUTHORIZED], Response::HTTP_UNAUTHORIZED);
        }
        $review = $entityManager->getRepository(Review::class)->find($id);

        if (!$review) {
            return new JsonResponse(['error' => 'Review not found', 'Status code' => Response::HTTP_NOT_FOUND], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['rating'])) {
            $review->setRating($data['rating']);
        }
        if (isset($data['comments'])) {
            $review->setComments($data['comments']);
        }

        $review->setCreatedAt(new \DateTime());

        $entityManager->flush();

        return new JsonResponse(['message' => 'Review updated', 'id' => $review->getId(), 'Status code' => Response::HTTP_OK], Response::HTTP_OK);
    }

    #[Rest\Delete('/api/v1/deleteReview/{id}', name: 'delete_Review')]
    public function deleteReview(EntityManagerInterface $entityManager, int $id, UserRepository $userRepository, Request $request, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        if (!$this->isAuthorised($userRepository, $request, $userPasswordHasher)) {
            return new JsonResponse(['error' => 'UNAUTHORIZED', 'status_code' => Response::HTTP_UNAUTHORIZED], Response::HTTP_UNAUTHORIZED);
        }
        $review = $entityManager->getRepository(Review::class)->find($id);
        if (!$review) {
            return new JsonResponse(['error' => 'Review not found', 'Status code' => Response::HTTP_NOT_FOUND], Response::HTTP_NOT_FOUND);
        }
        $entityManager->remove($review);
        $entityManager->flush();
        return new JsonResponse(['message' => 'Review deleted', 'Status code' => Response::HTTP_OK], Response::HTTP_OK);
    }
    public function isAuthorised(UserRepository $userRepository, Request $request, UserPasswordHasherInterface $userPasswordHasher)
    {
        $user = $userRepository->findOneBy(['username' => $request->getUser()]);
        //User found?
        if ($user) {
            $hashedPassword = $user->getPassword();
            if ($userPasswordHasher->isPasswordValid($user, $request->getPassword())) {
                // Passwords match
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}
