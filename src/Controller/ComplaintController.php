<?php

namespace App\Controller;

use App\Service\NotificationService;
use App\Service\SentimentServiceComplaints;   // ← nom correct

use App\Entity\Complaint;
use App\Entity\ComplaintCategory;
use App\Entity\ComplaintPriority;
use App\Repository\ComplaintRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/complaints')]
#[IsGranted('ROLE_USER')]
class ComplaintController extends AbstractController
{
    public function __construct(
        private NotificationService        $notificationService,
        private SentimentServiceComplaints $sentimentServiceComplaints  // ← nom correct
    ) {}

    #[Route('/', name: 'app_complaints_index')]
    public function index(ComplaintRepository $complaintRepository): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $complaints = $complaintRepository->findByUser($user);

        return $this->render('complaints/index.html.twig', [
            'complaints' => $complaints,
        ]);
    }

    #[Route('/new', name: 'app_complaints_new')]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger
    ): Response {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('new-complaint', $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Invalid CSRF token');
            }

            $complaint = new Complaint();
            $complaint->setSubject($request->request->get('subject'));
            $complaint->setDescription($request->request->get('description'));
            $complaint->setCategory(ComplaintCategory::from($request->request->get('category')));
                /** @var \App\Entity\User $user */
                $user = $this->getUser();
                $complaint->setSubmittedBy($user);

            // ── Analyse de sentiment via SentimentServiceComplaints ──
            $textToAnalyse = $complaint->getSubject() . ' ' . $complaint->getDescription();
            $sentiment     = $this->sentimentServiceComplaints->analyse($textToAnalyse);

            $complaint->setSentimentLabel($sentiment['label']);
            $complaint->setSentimentScore($sentiment['score']);
            $complaint->setSentimentSource($sentiment['source']);
            $complaint->setSentimentPrioritySuggestion($sentiment['priority_suggestion']);

            // Remontée automatique de priorité si très négatif
            if (
                $sentiment['priority_suggestion'] === 'URGENT'
                && $complaint->getPriority() !== ComplaintPriority::URGENT
            ) {
                $complaint->setPriority(ComplaintPriority::URGENT);
            } elseif (
                $sentiment['priority_suggestion'] === 'HIGH'
                && in_array($complaint->getPriority(), [ComplaintPriority::LOW, ComplaintPriority::MEDIUM])
            ) {
                $complaint->setPriority(ComplaintPriority::HIGH);
            }
            // ─────────────────────────────────────────────────────────

            // Gestion de la pièce jointe
            $attachmentFile = $request->files->get('attachment');
            if ($attachmentFile) {
                $originalFilename = pathinfo($attachmentFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename     = $slugger->slug($originalFilename);
                $newFilename      = $safeFilename . '-' . uniqid() . '.' . $attachmentFile->guessExtension();

                try {
                    $attachmentFile->move(
                        $this->getParameter('complaints_directory'),
                        $newFilename
                    );
                    $complaint->setAttachmentPath($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Failed to upload attachment');
                }
            }

            $em->persist($complaint);
            $em->flush();

            $this->notificationService->notifyComplaintSubmitted($complaint);

            $this->addFlash('success', 'Your complaint has been submitted successfully. We will review it shortly.');
            return $this->redirectToRoute('app_complaints_show', ['id' => $complaint->getId()]);
        }

        return $this->render('complaints/new.html.twig', [
            'categories' => ComplaintCategory::cases(),
        ]);
    }

    #[Route('/{id}', name: 'app_complaints_show', requirements: ['id' => '\d+'])]
    public function show(Complaint $complaint): Response
    {
        if ($complaint->getSubmittedBy() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You cannot view this complaint');
        }

        return $this->render('complaints/show.html.twig', [
            'complaint' => $complaint,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_complaints_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Complaint $complaint,
        EntityManagerInterface $em
    ): Response {
        if ($complaint->getSubmittedBy() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You cannot delete this complaint');
        }

        if (!$this->isCsrfTokenValid('delete-complaint-' . $complaint->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }

        if (!$complaint->isPending()) {
            $this->addFlash('error', 'You can only delete pending complaints');
            return $this->redirectToRoute('app_complaints_show', ['id' => $complaint->getId()]);
        }

        $em->remove($complaint);
        $em->flush();

        $this->addFlash('success', 'Complaint deleted successfully');
        return $this->redirectToRoute('app_complaints_index');
    }
}