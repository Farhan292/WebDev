<?php
// src/Controller/FilmController.php

namespace App\Controller;

use App\Entity\Film;
use App\Entity\Review;
use App\Entity\User;
use App\Form\Type\ReviewType;
use App\Form\Type\FilmType;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpClient\HttpClient;
use GuzzleHttp\Client as GuzzleClient;

class FilmController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    //This route displays all data for the film page
    #[Route('/searchFilm/{film}')]
    public function shrekSearch($film, Request $request): JsonResponse
    {
        $apiKey = '448df67f175d5b9e01160acf5629901d';
        $baseUrl = 'https://api.themoviedb.org/3';

        // Construct the API request URL using the $film parameter from the route
        $url = "$baseUrl/search/movie?query=$film&api_key=$apiKey";

        // Initialize GuzzleClient
        $client = new GuzzleClient();

        // Make the GET request to TMDb API
        $response = $client->request('GET', $url);

        // Get the JSON response body
        $content = $response->getBody()->getContents();

        // Decode the JSON response
        $movies = json_decode($content, true);

        // Initialize the release date and overview variables
        $releaseDate = null;
        $overview = null;

        // Search for the movie with the title containing the search term
        foreach ($movies['results'] as $movie) {
            if (stripos($movie['title'], $film) !== false) {
                // Check if the release date is available
                if (!empty($movie['release_date'])) {
                    $releaseDate = $movie['release_date'];
                }

                // Check if the overview is available
                if (!empty($movie['overview'])) {
                    $overview = $movie['overview'];
                }

                // Break the loop if both release date and overview are found
                if ($releaseDate && $overview) {
                    break;
                }
            }
        }

        // Return the release date as JSON response
        return new JsonResponse(['release_date' => $releaseDate]);
    }
    #[Route('/filmFind/{filmName}', name: 'filmShow')]
    public function show($filmName, ReviewRepository $reviewRepository, EntityManagerInterface $entityManager, Request $request): Response
    {
        $apiKey = '448df67f175d5b9e01160acf5629901d';
        $baseUrl = 'https://api.themoviedb.org/3';

        // Construct the API request URL using the $film parameter from the route
        $url = "$baseUrl/search/movie?query=$filmName&api_key=$apiKey";

        // Initialize GuzzleClient
        $client = new GuzzleClient();

        // Make the GET request to TMDb API
        $response = $client->request('GET', $url);

        // Get the JSON response body
        $content = $response->getBody()->getContents();

        // Decode the JSON response
        $movies = json_decode($content, true);

        // Initialize the release date and overview variables
        $releaseDate = null;
        $overview = null;

        // Search for the movie with the title containing the search term
        foreach ($movies['results'] as $movie) {
            if (stripos($movie['title'], $filmName) !== false) {
                // Check if the release date is available
                if (!empty($movie['release_date'])) {
                    $releaseDate = $movie['release_date'];
                }

                // Check if the overview is available
                if (!empty($movie['overview'])) {
                    $overview = $movie['overview'];
                }

                // Break the loop if both release date and overview are found
                if ($releaseDate && $overview) {
                    break;
                }
            }
        }
        $filmRepository = $entityManager->getRepository(Film::class);
        $film = $filmRepository->findOneBy(['name' => $filmName]);
        //Get film by name passed
        if (!$film) {
            throw $this->createNotFoundException('Film not found');
        }
        $reviews = $reviewRepository->findBy(['film' => $film]);
        //Get usernames for all reviews
        $usernames = [];
        foreach ($reviews as $review) {
            $userId = $review->getUser();
            $user = $entityManager->getRepository(User::class)->find($userId);

            if ($user) {
                $usernames[] = $user->getUsername();
            } else {
                $usernames[] = 'User not found';
            }
        }

        //Adding Reviews
        $review = new Review();
        $form = $this->createForm(ReviewType::class, $review, ['edit_mode' => false]);
        //Prefill review
        $form->handleRequest($request);
        $currentDateTime = new \DateTime();
        $review->setCreatedAt($currentDateTime);

        if ($form->isSubmitted() && $form->isValid()) {
            $id = $this->getUser()->getId();
            //Prefill review
            $review->setUser($id);
            $review->setFilm($film->getId());
            $review->setCreatedAt($currentDateTime);
            //Add and save to database
            $entityManager->persist($review);
            $entityManager->flush();

            return $this->redirectToRoute('filmShow', ['filmName' => $filmName]);
        }
        //Films to output
        $films = $entityManager
            ->getRepository(Film::class)
            ->createQueryBuilder('f')
            ->where('f.approved = 1')
            ->getQuery()
            ->getResult();

        shuffle($films); //Randomise
        $fourFilms = array_slice($films, 0, 4);
        return $this->render('showFilm.html.twig', [
            'film' => $film,
            'reviews' => $reviews,
            'usernames' => $usernames,
            'form' => $form->createView(),
            'fourFilms' => $fourFilms,
            'date' => $releaseDate,
            'overview' => $ip,
        ]);
    }
    //This route allows users to search
    #[Route('/search', name: 'searchFilms')]
    public function searchFilms(Request $request, EntityManagerInterface $entityManager): Response
    {
        $query = $request->query->get('query');

        // Use the EntityManager to perform a simple query
        $films = $entityManager
            ->createQuery('SELECT f FROM App\Entity\Film f WHERE f.name LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->getResult();

        return $this->render('searchResults.html.twig', [
            'films' => $films,
        ]);
    }
    #[Route('/film', name: 'film')]
    public function filmHome(): Response
    {
        return $this->render('film.html.twig');
    }
    //Gets all reviews for user that's signed in
    #[Route('/myReviews', name: 'myReviews')]
    public function myReviews(EntityManagerInterface $entityManager): Response
    {
        //Get user ID of the logged-in user
        $userId = $this->getUser()->getId();

        //Get reviews for user
        $reviews = $entityManager
            ->getRepository(Review::class)
            ->findBy(['user' => $userId]);

        //Get film data for review
        $filmArray = [];
        $index = 0;
        foreach ($reviews as $review) {
            $filmId = $review->getFilm();
            $film = $entityManager->getRepository(Film::class)->find($filmId);
            $filmArray[$index] = $film;
            $index++;
        }
        //Unapproved films won't be sent to twig
        $approvalFilms = $entityManager
            ->getRepository(Film::class)
            ->findBy(['approved' => 0]);
        return $this->render('myReviews.html.twig', [
            'reviews' => $reviews,
            'films' => $filmArray,
            'approvalFilms' => $approvalFilms,
        ]);
    }
    //This route allows uses to add film
    #[Route('/addFilm', name: 'addFilm')]
    public function addFilm(Request $request, EntityManagerInterface $entityManager): Response
    {
        $film = new Film();
        $form = $this->createForm(FilmType::class, $film);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //File upload
            $uploadedFile = $form->get('image_path')->getData();
            //Store in public/images

            if ($uploadedFile) {
                $newFilename = $this->uploadFile($uploadedFile);//Internal method
                $film->setImagePath($newFilename);
            }

            $film->setApproved(0);
            //Add and save to database
            $entityManager->persist($film);
            $entityManager->flush();

            return $this->redirectToRoute('film');
        }

        return $this->render('addFilm.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    //Handles file uploads
    private function uploadFile(UploadedFile $file): string
    {
        $uploadDirectory = $this->getParameter('images_directory');
        //Store in public/images

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $newFilename = $originalFilename.'-'.uniqid().'.'.$file->guessExtension();//Ensures no errors with duplicate names

        try {
            $file->move($uploadDirectory, $newFilename);
        } catch (FileException $e) {
            throw new \Exception('Unable to upload the file');
        }

        return $newFilename;
    }
    //Admin can approve film
    #[Route('/approveFilm/{id}', name: 'approveFilm')]
    public function approveFilm($id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $filmRepository = $entityManager->getRepository(Film::class);
        $film = $filmRepository->find($id);
        $film->setApproved(1);
        //Add and save database
        $entityManager->persist($film);
        $entityManager->flush();
        return $this->redirectToRoute('myReviews');
    }
    //Admin can decline film
    #[Route('/declineFilm/{id}', name: 'declineFilms')]
    public function declineFilm($id, EntityManagerInterface $entityManager): Response
    {
        $filmRepository = $entityManager->getRepository(Film::class);
        $film = $filmRepository->find($id);
        //Remove and save database
        $entityManager->remove($film);
        $entityManager->flush();

        return $this->redirectToRoute('myReviews');
    }
    #[Route('/autoFill', name: 'autoFill')]
    public function autoFill(Request $request): JsonResponse
    {
        $filmName = $request->query->get('filmName');
        $apiKey = '448df67f175d5b9e01160acf5629901d';
        $baseUrl = 'https://api.themoviedb.org/3';
        $url = "$baseUrl/search/movie?query=" . urlencode($filmName) . "&api_key=$apiKey";
        $client = new GuzzleClient();
        $response = $client->request('GET', $url);
        $content = $response->getBody()->getContents();
        $movies = json_decode($content, true);
        $response = [];
        foreach ($movies['results'] as $movie) {
            if (stripos($movie['title'], $filmName) !== false) {
                $response['description'] = !empty($movie['overview']) ? $movie['overview'] : '';
                $response['name'] = $movie['title'];
                break;
            }
        }
        return new JsonResponse($response);
    }
    #[Route('/autoFill2', name: 'autoFill2')]
    public function autoFill2(Request $request): JsonResponse
    {
        $filmName = $request->query->get('filmName');
        $apiKey = 'e2fac780';
        $baseUrl = 'http://www.omdbapi.com';
        $url = "$baseUrl/?apikey=$apiKey&t=" . urlencode($filmName);
        $client = new GuzzleClient();
        $response = $client->request('GET', $url);
        $content = $response->getBody()->getContents();
        $movie = json_decode($content, true);
        $response = [];
        if (!empty($movie['Title'])) {
            // Extract the required information
            $response['director'] = $movie['Director'];
            $response['runtime'] = $movie['Runtime'];
            $response['cast'] = $movie['Actors'];
        }
        return new JsonResponse($response);
    }
}