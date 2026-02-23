<?php

namespace App\Controller\Admin;

use App\Entity\Tournoi;
use App\Form\TournoiType;
use App\Repository\TournoiRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

use App\Entity\TypeTournoi;

#[Route('/admin/tournoi')]
#[IsGranted('ROLE_ADMIN')]
class AdminTournoiController extends AbstractController
{
    #[Route('/', name: 'admin_tournoi_index')]
    public function index(Request $request, TournoiRepository $tournoiRepository): Response
    {
        $search = $request->query->get('search');
        $type = $request->query->get('type');
        $sort = $request->query->get('sort', 'dateDebut');
        $direction = $request->query->get('direction', 'DESC');

        // Allow sorting by these fields
        $allowedSorts = ['nom', 'dateDebut', 'dateFin', 'typeTournoi'];
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'dateDebut';
        }

        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';

        $tournois = $tournoiRepository->findBySearchAndFilter($search, $type, $sort, $direction);

        return $this->render('admin/tournoi/index.html.twig', [
            'tournois' => $tournois,
            'search' => $search,
            'typeFilter' => $type,
            'sort' => $sort,
            'direction' => $direction,
            'types' => TypeTournoi::cases(),
        ]);
    }

    #[Route('/create', name: 'admin_tournoi_create')]
    public function create(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $tournoi = new Tournoi();
        $form = $this->createForm(TournoiType::class, $tournoi);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle image upload
            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('image')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('tournois_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                    $this->addFlash('error', 'Error uploading image');
                }

                $tournoi->setImage($newFilename);
            }

            $entityManager->persist($tournoi);
            $entityManager->flush();

            $this->addFlash('success', 'Tournoi created successfully');

            return $this->redirectToRoute('admin_tournoi_index');
        }

        return $this->render('admin/tournoi/create.html.twig', [
            'tournoi' => $tournoi,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/generate-description', name: 'admin_tournoi_generate_description', methods: ['POST'])]
    public function generateDescription(Request $request): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $name = $data['name'] ?? '';
        $type = $data['type'] ?? '';

        if (empty($name)) {
            return $this->json(['error' => 'Le nom du tournoi est requis.'], 400);
        }

        try {
            $apiKey = $this->getParameter('gemini_api_key');
            $prompt = "Tu es un expert passionné en eSport. Génère une description UNIQUE, professionnelle et engageante (3-4 phrases) en français pour ce tournoi. Sois créatif et varie le style à chaque fois.\n- Nom : {$name}\n- Type : {$type}\n- Graine de créativité : " . rand(1000, 9999) . "\n\nRéponds uniquement avec la description, sans titre ni préambule.";

            // Using HuggingFace Router (free tier)
            $url = "https://router.huggingface.co/v1/chat/completions";
            $payload = json_encode([
                'model' => 'Qwen/Qwen2.5-72B-Instruct',
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ],
                'max_tokens' => 350,
                'temperature' => 1.1,
                'top_p' => 0.95,
            ]);

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ]);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                return $this->json(['error' => 'Erreur de connexion (CURL): ' . $curlError], 500);
            }

            if ($httpCode !== 200) {
                $errData = json_decode($response, true);
                return $this->json(['error' => 'IA non disponible (' . $httpCode . '): ' . ($errData['error']['message'] ?? 'Erreur inconnue')], 500);
            }

            $decoded = json_decode($response, true);
            // Check for potential nested structure or direct access
            $text = $decoded['choices'][0]['message']['content'] ?? null;

            if (!$text) {
                return $this->json(['error' => 'Format de réponse IA invalide.'], 500);
            }

            return $this->json(['description' => trim($text)]);

        } catch (\Throwable $e) {
            return $this->json(['error' => 'Exception système: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/{id}', name: 'admin_tournoi_show', methods: ['GET'])]
    public function show(Tournoi $tournoi): Response
    {
        return $this->render('admin/tournoi/show.html.twig', [
            'tournoi' => $tournoi,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_tournoi_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Tournoi $tournoi, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(TournoiType::class, $tournoi);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle image upload
            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('image')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('tournois_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', 'Error uploading image');
                }

                $tournoi->setImage($newFilename);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Tournoi updated successfully');

            return $this->redirectToRoute('admin_tournoi_index');
        }

        return $this->render('admin/tournoi/edit.html.twig', [
            'tournoi' => $tournoi,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'admin_tournoi_delete', methods: ['POST'])]
    public function delete(Request $request, Tournoi $tournoi, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete-'.$tournoi->getId(), $request->request->get('_token'))) {
            $entityManager->remove($tournoi);
            $entityManager->flush();
            $this->addFlash('success', 'Tournoi deleted successfully');
        }

        return $this->redirectToRoute('admin_tournoi_index');
    }
}
