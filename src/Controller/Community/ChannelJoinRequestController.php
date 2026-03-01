<?php

namespace App\Controller\Community;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Channel;
use App\Entity\ChannelJoinRequest;
use App\Entity\Notification;
use App\Entity\NotificationType;
use App\Repository\ChannelJoinRequestRepository;
use App\Repository\UserRepository;
use App\Service\ChannelAccessService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

final class ChannelJoinRequestController extends AbstractController
{
    #[Route('/channel/join/request', name: 'app_channel_join_request')]
    public function index(): Response
    {
        return $this->render('channel_join_request/index.html.twig', [
            'controller_name' => 'ChannelJoinRequestController',
        ]);
    }

    #[Route('/channels/{id}/request-access', name: 'community_channel_request_access', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function requestAccess(
        Channel                      $channel,
        Request                      $request,
        ChannelAccessService         $access,
        ChannelJoinRequestRepository $reqRepo,
        UserRepository               $userRepo,
        EntityManagerInterface       $em
    ): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if (!$this->isCsrfTokenValid('request_access_' . $channel->getId(), (string)$request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        // Only makes sense for private channels
        if ($channel->getType() !== Channel::TYPE_PRIVATE) {
            return $this->redirectToRoute('community_channels_show', ['id' => $channel->getId()]);
        }

        $user = $this->getUser();

        // If user already has access (member/creator), don't create request
        if ($access->canAccess($channel, $user)) {
            return $this->redirectToRoute('community_channels_show', ['id' => $channel->getId()]);
        }

        // If already pending, do nothing
        $existing = $reqRepo->findOneBy([
            'channel' => $channel,
            'requester' => $user,
            'status' => 'pending',
        ]);

        if ($existing) {
            return $this->redirectToRoute('community_channels_show', ['id' => $channel->getId()]);
        }

        // Create request
        $jr = new ChannelJoinRequest();
        $jr->setChannel($channel);
        $jr->setRequester($user);
        $jr->setStatus('pending');
        $jr->setRequestedAt(new \DateTimeImmutable());
        $em->persist($jr);

        // Notify creator
        $creatorEmail = trim((string) $channel->getCreatedBy());
        $creator = $userRepo->findOneBy(['email' => $creatorEmail]);
        if (!$creator) {
            $creator = $userRepo->findOneBy(['username' => $creatorEmail]);
        }
        if ($creator) {
            $notif = new Notification();
            $notif->setUser($creator);
            $notif->setType(NotificationType::CHANNEL_ACCESS_REQUEST);
            $notif->setMessage(sprintf('%s requested access to private channel "%s"', $user->getUserIdentifier(), $channel->getName()));
            $notif->setLink($this->generateUrl('community_my_channel_requests'));
            $em->persist($notif);
        }

        $em->flush();

        return $this->redirectToRoute('community_channels_show', ['id' => $channel->getId()]);
    }
}
