<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\TournoiRepository;
use App\Repository\TeamRepository;
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
        TeamRepository    $teamRepository,
    ): Response {
        // ── Même méthode que le backoffice AdminUserController ────────
        $stats = $userRepository->getGlobalStats();

        // ── Nombre de teams actives ───────────────────────────────────
        $totalTeams = $teamRepository->count(['isActive' => true]);

        // ── Nombre de tournois ────────────────────────────────────────
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
            'totalPlayers'     => $stats['total'],
            'totalCoaches'     => $stats['coaches'],
            'totalTeams'       => $totalTeams,
            'totalTournaments' => $totalTournaments,
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