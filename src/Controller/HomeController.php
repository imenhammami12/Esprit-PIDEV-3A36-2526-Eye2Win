<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\TournoiRepository;
use App\Repository\UserRepository;
use App\Repository\VideoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(
        VideoRepository   $videoRepository,
        UserRepository    $userRepository,
        TournoiRepository $tournoiRepository,
    ): Response {
        // ── Statistiques réelles ──────────────────────────────────────
        $stats = $userRepository->getGlobalStats(); // appel unique, zéro requête extra

        $totalPlayers     = $stats['total'];
        $totalCoaches     = $stats['coaches'];
        $satisfaction     = 95; // valeur fixe tant qu'il n'y a pas de système de reviews

        $totalTournaments = $tournoiRepository->countAllTournois();

        // ── Vidéos de l'utilisateur connecté (6 dernières) ───────────
        $videos = [];
        $user   = $this->getUser();
        if ($user instanceof User) {
            $videos = $videoRepository->findBy(
                ['uploadedBy' => $user],
                ['uploadedAt' => 'DESC'],
                6
            );
        }

        return $this->render('home/index.html.twig', [
            'videos'           => $videos,
            'totalPlayers'     => $totalPlayers,
            'totalTournaments' => $totalTournaments,
            'totalCoaches'     => $totalCoaches,
            'satisfaction'     => $satisfaction,
        ]);
    }

    #[Route('/dashboard', name: 'app_dashboard', methods: ['GET'])]
    public function dashboard(VideoRepository $videoRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user   = $this->getUser();
        $videos = [];

        if ($user instanceof User) {
            $videos = $videoRepository->findByUser($user);
        }

        return $this->render('home/dashboard.html.twig', [
            'videos' => $videos,
        ]);
    }

    #[Route('/planning', name: 'app_planning')]
    public function planning(): Response
    {
        return $this->render('home/planning.html.twig');
    }
}