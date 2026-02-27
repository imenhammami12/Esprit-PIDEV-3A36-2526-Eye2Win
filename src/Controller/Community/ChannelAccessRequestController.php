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
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

final class ChannelAccessRequestController extends AbstractController
{
//    #[Route('/channel/access/request', name: 'app_channel_access_request')]
//    public function index(): Response
//    {
//        return $this->render('channel_access_request/index.html.twig', [
//            'controller_name' => 'ChannelAccessRequestController',
//        ]);
//    }
//    #[Route('/community/channels/{id}/request-access', name: 'community_channel_request_access', methods: ['POST'])]
//    public function requestAccess(
//        Channel                      $channel,
//        Request                      $request,
//        ChannelJoinRequestRepository $reqRepo,
//        UserRepository               $userRepo,
//        EntityManagerInterface       $em
//    ): RedirectResponse
//    {
//        $this->denyAccessUnlessGranted('ROLE_USER');
//
//        if (!$this->isCsrfTokenValid('request_access_' . $channel->getId(), $request->request->get('_token'))) {
//            throw $this->createAccessDeniedException();
//        }
//
//        $user = $this->getUser();
//
//        // If already requested and pending → no duplicate
//        $existing = $reqRepo->findOneBy([
//            'channel' => $channel,
//            'requester' => $user,
//            'status' => 'pending',
//        ]);
//        if ($existing) {
//            return $this->redirectToRoute('community_channels_show', ['id' => $channel->getId()]);
//        }
//
//        $jr = new ChannelJoinRequest();
//        $jr->setChannel($channel);
//        $jr->setRequester($user);
//        $jr->setStatus('pending');
//        $jr->setRequestedAt(new \DateTimeImmutable());
//        $em->persist($jr);
//
//        // Notify creator (if exists)
//        $creator = $userRepo->findOneBy(['email' => $channel->getCreatedBy()]);
//        if ($creator) {
//            $notif = new Notification();
//            $notif->setUser($creator);
//            $notif->setType(NotificationType::SYSTEM); // adjust to your enum values
//            $notif->setMessage(sprintf('%s requested to join "%s"', $user->getUserIdentifier(), $channel->getName()));
//            $notif->setLink($this->generateUrl('community_my_channel_requests'));
//            $em->persist($notif);
//        }
//
//        $em->flush();
//
//        return $this->redirectToRoute('community_channels_show', ['id' => $channel->getId()]);
//    }
}
