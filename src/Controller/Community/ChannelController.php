<?php

namespace App\Controller\Community;

use App\Entity\Channel;
use App\Entity\Message;
use App\Form\ChannelType;
use App\Form\MessageType;
use App\Repository\ChannelRepository;
use App\Repository\MessageRepository;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\ChannelAccessService;
use App\Repository\ChannelJoinRequestRepository;
use App\Repository\ChannelMemberRepository;

class ChannelController extends AbstractController
{
    #[Route('/channels', name: 'community_channels_index')]
    public function index(ChannelRepository $repo,
                          NotificationRepository $notificationRepo,
                          ChannelJoinRequestRepository $reqRepo,
                          ChannelMemberRepository $memberRepo
    ): Response
    {
        $channels = $repo->findVisibleForUser($this->getUser());
        $channelNotifications = [];

        if ($this->getUser()) {
            $channelNotifications = $notificationRepo->findChannelNotificationsForUser($this->getUser());
        }

        $pendingChannelIds = [];

        if ($this->getUser()) {
            $pending = $reqRepo->createQueryBuilder('r')
                ->select('IDENTITY(r.channel) as cid')
                ->andWhere('r.requester = :me')
                ->andWhere('r.status = :pending')
                ->setParameter('me', $this->getUser())
                ->setParameter('pending', 'pending')
                ->getQuery()
                ->getArrayResult();

            $pendingChannelIds = array_map(fn($row) => (int) $row['cid'], $pending);
        }

        $memberChannelIds = [];

        if ($this->getUser()) {
            $memberships = $memberRepo->createQueryBuilder('m')
                ->select('IDENTITY(m.channel) as cid')
                ->andWhere('m.user = :me')
                ->setParameter('me', $this->getUser())
                ->getQuery()
                ->getArrayResult();

            $memberChannelIds = array_map(fn($row) => (int) $row['cid'], $memberships);
        }

        return $this->render('community/channel/index.html.twig', [
            'channels' => $channels,
            'channelNotifications' => $channelNotifications,
            'pendingChannelIds' => $pendingChannelIds,
            'memberChannelIds' => $memberChannelIds,
        ]);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/channels/new', name: 'community_channels_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $channel = new Channel();
        $form = $this->createForm(ChannelType::class, $channel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $now = new \DateTimeImmutable();

            $channel->setStatus(Channel::STATUS_PENDING);
            $channel->setIsActive(false);
            $channel->setCreatedAt($now);
            $channel->setCreatedBy($this->getUser()->getUserIdentifier());

            $em->persist($channel);
            $em->flush();

            $this->addFlash('success', 'Channel created ✅ Waiting for admin validation.');
            return $this->redirectToRoute('community_channels_index');
        }

        return $this->render('community/channel/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/channels/{id}', name: 'community_channels_show', requirements: ['id' => '\d+'])]
    public function show(Channel $channel,
                         MessageRepository $messageRepo,
                         Request $request,
                         EntityManagerInterface $em,
                         ChannelRepository $channelRepo,
                         ChannelAccessService $access,
                         NotificationRepository $notificationRepo,
                         ChannelJoinRequestRepository $reqRepo): Response
    {
        $visible = $channelRepo->findVisibleForUser($this->getUser());
        $visibleIds = array_map(fn($c) => $c->getId(), $visible);

        if (!in_array($channel->getId(), $visibleIds, true)) {
            throw $this->createAccessDeniedException("Channel non accessible.");
        }

        if (!$access->canAccess($channel, $this->getUser())) {
            $user = $this->getUser();
            $pending = null;
            if ($user) {
                $pending = $reqRepo->findOneBy([
                    'channel' => $channel,
                    'requester' => $user,
                    'status' => 'pending',
                ]);
            }

            return $this->render('community/channel/locked.html.twig', [
                'channel' => $channel,
                'pendingRequest' => $pending,
            ]);
        }

        $messages = $messageRepo->findForChannelAll($channel->getId());
        $editId = $request->query->getInt('edit', 0);
        $editFormView = null;

        if ($editId > 0 && $this->isGranted('ROLE_USER')) {
            $messageToEdit = $messageRepo->find($editId);

            if (
                $messageToEdit
                && $messageToEdit->getChannel()->getId() === $channel->getId()
                && $messageToEdit->getSenderEmail() === $this->getUser()->getUserIdentifier()
                && !$messageToEdit->isDeleted()
            ) {
                $editForm = $this->createForm(MessageType::class, $messageToEdit, [
                    'action' => $this->generateUrl('community_message_edit', ['id' => $messageToEdit->getId()]),
                    'method' => 'POST',
                ]);

                $editFormView = $editForm->createView();
            }
        }

        $message = new Message();
        $form = $this->createForm(MessageType::class, $message, [
            'action' => $this->generateUrl('community_message_send', ['id' => $channel->getId()]),
            'method' => 'POST',
        ]);

        return $this->render('community/channel/show.html.twig', [
            'channel' => $channel,
            'messages' => $messages,
            'messageForm' => $form->createView(),
            'editId' => $editId,
            'editForm' => $editFormView,
        ]);
    }

    /**
     * Polling endpoint — returns new messages after a given ID
     */
    #[Route('/channels/{id}/poll', name: 'community_channels_poll', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function poll(Channel $channel, Request $request, MessageRepository $messageRepo, ChannelAccessService $access): JsonResponse
    {
        if (!$access->canAccess($channel, $this->getUser())) {
            return $this->json(['messages' => []]);
        }

        $afterId = (int) $request->query->get('after', 0);

        $messages = $messageRepo->createQueryBuilder('m')
            ->where('m.channel = :channel')
            ->andWhere('m.id > :afterId')
            ->setParameter('channel', $channel)
            ->setParameter('afterId', $afterId)
            ->orderBy('m.id', 'ASC')
            ->setMaxResults(50)
            ->getQuery()
            ->getResult();

        $data = array_map(function (Message $m) {
            $attachments = [];
            foreach ($m->getAttachments() as $a) {
                $attachments[] = [
                    'url'  => $a->getUrl(),
                    'name' => $a->getOriginalName(),
                    'mime' => $a->getMimeType(),
                    'size' => $a->getSize(),
                ];
            }

            return [
                'id'          => $m->getId(),
                'content'     => $m->getContent(),
                'senderName'  => $m->getSenderName(),
                'senderEmail' => $m->getSenderEmail(),
                'sentAt'      => $m->getSentAt()?->format('H:i'),
                'isDeleted'   => $m->isDeleted(),
                'attachments' => $attachments,
            ];
        }, $messages);

        return $this->json(['messages' => $data]);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/channels/{id}/edit', name: 'community_channels_edit', requirements: ['id' => '\d+'])]
    public function edit(Channel $channel, Request $request, EntityManagerInterface $em): Response
    {
        $identifier = $this->getUser()?->getUserIdentifier();
        if ($channel->getCreatedBy() !== $identifier) {
            throw $this->createAccessDeniedException("you can't modify this channel.");
        }

        $form = $this->createForm(ChannelType::class, $channel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Channel edited ✅');
            return $this->redirectToRoute('community_channels_index');
        }

        return $this->render('community/channel/edit.html.twig', [
            'channel' => $channel,
            'form' => $form->createView(),
        ]);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/channels/{id}/delete', name: 'community_channels_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Channel $channel, Request $request, EntityManagerInterface $em): Response
    {
        $identifier = $this->getUser()?->getUserIdentifier();
        if ($channel->getCreatedBy() !== $identifier) {
            throw $this->createAccessDeniedException("you can't delete this channel.");
        }

        if (!$this->isCsrfTokenValid('delete_channel_'.$channel->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('community_channels_index');
        }

        $em->remove($channel);
        $em->flush();

        $this->addFlash('success', 'Channel supprimé ✅');
        return $this->redirectToRoute('community_channels_index');
    }
}