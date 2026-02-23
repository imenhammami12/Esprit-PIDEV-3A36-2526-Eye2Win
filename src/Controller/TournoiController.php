<?php

namespace App\Controller;

use App\Entity\Tournoi;
use App\Repository\TournoiRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/tournaments')]
#[IsGranted('ROLE_USER')]
class TournoiController extends AbstractController
{
    #[Route('/landing', name: 'app_tournoi_landing')]
    public function landing(): Response
    {
        return $this->render('tournoi/landing.html.twig');
    }

    #[Route('/', name: 'app_tournoi_index')]
    public function index(TournoiRepository $tournoiRepository): Response
    {
        return $this->render('tournoi/index.html.twig', [
            'tournaments' => $tournoiRepository->findAll(),
        ]);
    }

    #[Route('/{id}', name: 'app_tournoi_show')]
    public function show(Tournoi $tournoi): Response
    {
        return $this->render('tournoi/show.html.twig', [
            'tournament' => $tournoi,
            'matches' => $tournoi->getMatchs(),
        ]);
    }

    #[Route('/{id}/checkout', name: 'app_tournoi_checkout', methods: ['POST'])]
    public function checkout(Tournoi $tournoi, \Symfony\Component\HttpFoundation\Request $request): Response
    {
        if (!$this->isCsrfTokenValid('checkout' . $tournoi->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if ($tournoi->getParticipants()->contains($user)) {
            $this->addFlash('warning', 'Vous êtes déjà inscrit à ce tournoi.');
            return $this->redirectToRoute('app_tournoi_show', ['id' => $tournoi->getId()]);
        }

        if ($tournoi->getPrix() <= 0) {
            return $this->redirectToRoute('app_tournoi_inscription', ['id' => $tournoi->getId()]);
        }

        \Stripe\Stripe::setApiKey($this->getParameter('stripe_secret_key'));

        $checkoutSession = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => 'Inscription au tournoi : ' . $tournoi->getNom(),
                    ],
                    'unit_amount' => (int) ($tournoi->getPrix() * 100), // Stripe expects amount in cents
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $this->generateUrl('app_tournoi_payment_success', ['id' => $tournoi->getId()], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL),
            'cancel_url' => $this->generateUrl('app_tournoi_payment_cancel', ['id' => $tournoi->getId()], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL),
            'customer_email' => $user->getEmail(),
        ]);

        return $this->redirect($checkoutSession->url);
    }

    #[Route('/{id}/payment/success', name: 'app_tournoi_payment_success')]
    public function paymentSuccess(Tournoi $tournoi, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$tournoi->getParticipants()->contains($user)) {
            $tournoi->addParticipant($user);
            $entityManager->flush();

            // Send confirmation email
            $email = (new TemplatedEmail())
                ->from(new Address('chaimaamri104@gmail.com', 'Eye2Win Support'))
                ->to($user->getEmail())
                ->subject('Confirmation d\'inscription et paiement : ' . $tournoi->getNom())
                ->htmlTemplate('emails/registration.html.twig')
                ->context([
                    'user' => $user,
                    'tournoi' => $tournoi,
                ]);

            try {
                $mailer->send($email);
            } catch (\Exception $e) {
                // Log error or handle it silently since registration is done
            }
        }

        return $this->render('tournoi/registration_success.html.twig', [
            'tournoi' => $tournoi,
        ]);
    }

    #[Route('/{id}/payment/cancel', name: 'app_tournoi_payment_cancel')]
    public function paymentCancel(Tournoi $tournoi): Response
    {
        $this->addFlash('error', 'Le paiement a été annulé. L\'inscription n\'a pas pu être finalisée.');
        return $this->redirectToRoute('app_tournoi_show', ['id' => $tournoi->getId()]);
    }

    #[Route('/{id}/inscription', name: 'app_tournoi_inscription', methods: ['POST'])]
    public function inscription(Tournoi $tournoi, \Symfony\Component\HttpFoundation\Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
    {
        if (!$this->isCsrfTokenValid('inscription' . $tournoi->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        // If price is > 0, redirect to checkout
        if ($tournoi->getPrix() > 0) {
            return $this->redirectToRoute('app_tournoi_checkout', ['id' => $tournoi->getId()]);
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if ($tournoi->getParticipants()->contains($user)) {
            $this->addFlash('warning', 'Vous êtes déjà inscrit à ce tournoi.');
            return $this->redirectToRoute('app_tournoi_show', ['id' => $tournoi->getId()]);
        }

        $tournoi->addParticipant($user);
        $entityManager->flush();

        // Send confirmation email
        $email = (new TemplatedEmail())
            ->from(new Address('chaimaamri104@gmail.com', 'Eye2Win Support'))
            ->to($user->getEmail())
            ->subject('Confirmation d\'inscription au tournoi : ' . $tournoi->getNom())
            ->htmlTemplate('emails/registration.html.twig')
            ->context([
                'user' => $user,
                'tournoi' => $tournoi,
            ]);

        try {
            $mailer->send($email);
            $this->addFlash('success', 'Inscription réussie ! Un email de confirmation vous a été envoyé.');
        } catch (\Exception $e) {
            $this->addFlash('warning', 'Inscription réussie, mais l\'envoi de l\'email a échoué : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_tournoi_show', ['id' => $tournoi->getId()]);
    }
}
