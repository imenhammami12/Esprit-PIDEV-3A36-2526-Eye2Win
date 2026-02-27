<?php

namespace App\Controller\Community;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\ChannelJoinRequest;
use App\Entity\ChannelMember;
use App\Entity\Notification;
use App\Entity\NotificationType;
use App\Repository\ChannelJoinRequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

final class MyChannelRequestsController extends AbstractController
{
    #[Route('/community/my-channels/requests', name: 'community_my_channel_requests', methods: ['GET'])]
    public function index(ChannelJoinRequestRepository $repo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $meEmail = $this->getUser()->getUserIdentifier();

        // all pending requests for channels created by me
        $requests = $repo->createQueryBuilder('r')
            ->join('r.channel', 'c')
            ->andWhere('r.status = :pending')
            ->andWhere('c.createdBy = :me')
            ->setParameter('pending', 'pending')
            ->setParameter('me', $meEmail)
            ->orderBy('r.requestedAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('community/requests/my_channel_requests.html.twig', [
            'requests' => $requests,
        ]);
    }

    #[Route('/community/requests/{id}/approve', name: 'community_request_approve', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function approve(ChannelJoinRequest $req, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $channel = $req->getChannel();
        $meEmail = $this->getUser()->getUserIdentifier();

        // Only creator can approve
        if ($channel->getCreatedBy() !== $meEmail) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('approve_req_' . $req->getId(), (string)$request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        if ($req->getStatus() !== 'pending') {
            return $this->redirectToRoute('community_my_channel_requests');
        }

        // Create membership
        $member = new ChannelMember();
        $member->setChannel($channel);
        $member->setUser($req->getRequester());
        $member->setJoinedAt(new \DateTimeImmutable());
        $em->persist($member);

        // Update request
        $req->setStatus('approved');
        $req->setDecidedAt(new \DateTimeImmutable());
        $req->setDecidedByEmail($meEmail);

        // Notify requester
        $notif = new Notification();
        $notif->setUser($req->getRequester());
        $notif->setType(NotificationType::CHANNEL_ACCESS_APPROVED);
        $notif->setMessage(sprintf('Your access request to "%s" was approved ✅', $channel->getName()));
        $notif->setLink($this->generateUrl('community_channels_show', ['id' => $channel->getId()]));
        $em->persist($notif);

        $em->flush();

        return $this->redirectToRoute('community_my_channel_requests');
    }

    #[Route('/community/requests/{id}/reject', name: 'community_request_reject', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function reject(ChannelJoinRequest $req, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $channel = $req->getChannel();
        $meEmail = $this->getUser()->getUserIdentifier();

        if ($channel->getCreatedBy() !== $meEmail) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('reject_req_' . $req->getId(), (string)$request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        if ($req->getStatus() !== 'pending') {
            return $this->redirectToRoute('community_my_channel_requests');
        }

        $reason = trim((string)$request->request->get('reason'));
        $req->setStatus('rejected');
        $req->setReason($reason ?: null);
        $req->setDecidedAt(new \DateTimeImmutable());
        $req->setDecidedByEmail($meEmail);

        // Notify requester
        $notif = new Notification();
        $notif->setUser($req->getRequester());
        $notif->setType(NotificationType::CHANNEL_ACCESS_REJECTED);
        $notif->setMessage(sprintf('Your access request to "%s" was rejected ❌', $channel->getName()));
        $notif->setLink($this->generateUrl('community_channels_index'));
        $em->persist($notif);

        $em->flush();

        return $this->redirectToRoute('community_my_channel_requests');
    }
}
