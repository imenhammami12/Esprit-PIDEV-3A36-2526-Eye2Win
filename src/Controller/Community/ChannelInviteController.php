<?php

namespace App\Controller\Community;

use App\Entity\Channel;
use App\Entity\ChannelInvite;
use App\Entity\ChannelJoinRequest;
use App\Entity\ChannelMember;
use App\Entity\Notification;
use App\Entity\NotificationType;
use App\Repository\ChannelInviteRepository;
use App\Repository\ChannelJoinRequestRepository;
use App\Repository\ChannelMemberRepository;
use App\Repository\UserRepository;
use App\Service\ChannelAccessService;
use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\ByteString;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ChannelInviteController extends AbstractController
{
    #[IsGranted('ROLE_USER')]
    #[Route('/channels/{id}/invites', name: 'community_invite_create', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function createInvite(
        Channel $channel,
        Request $request,
        EntityManagerInterface $em,
        ChannelAccessService $access
    ): Response {
        // only creator can create invite
        if (!$access->isCreator($channel, $this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('create_invite_'.$channel->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $mode = (string) $request->request->get('mode', 'request_only'); // request_only | auto_join
        $mode = strtolower(trim($mode));
        if (!in_array($mode, ['request_only', 'auto_join'], true)) {
            $mode = 'request_only';
        }

        $invite = new ChannelInvite();
        $invite->setChannel($channel);
        $invite->setToken(ByteString::fromRandom(32)->toString()); // <= 64 chars ok
        $invite->setCreatedByEmail($this->getUser()->getUserIdentifier());
        $invite->setMode($mode);
        $invite->setUses(0);
        $invite->setIsActive(true);

        // optional settings (you can enable later)
        // $invite->setExpiresAt((new \DateTimeImmutable())->modify('+3 days'));
        // $invite->setMaxUses(10);

        $em->persist($invite);
        $em->flush();
        //dd($request->request->all());

        return $this->redirectToRoute('community_invite_show', ['token' => $invite->getToken()]);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/community/invites/{token}', name: 'community_invite_show', methods: ['GET'])]
    public function showInvite(string $token, ChannelInviteRepository $repo): Response
    {
        $invite = $repo->findOneBy(['token' => $token]);
        if (!$invite || !$invite->isActive()) {
            throw $this->createNotFoundException();
        }

        // only creator can view invite page
        if ($invite->getCreatedByEmail() !== $this->getUser()->getUserIdentifier()) {
            throw $this->createAccessDeniedException();
        }

        $openUrl = $this->generateUrl(
            'community_invite_open',
            ['token' => $invite->getToken()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return $this->render('community/invite/show.html.twig', [
            'invite' => $invite,
            'openUrl' => $openUrl,
        ]);
    }

    #[Route('/community/invites/{token}/qr.png', name: 'community_invite_qr', methods: ['GET'])]
    public function qr(string $token): Response
    {
        $openUrl = $this->generateUrl(
            'community_invite_open',
            ['token' => $token],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $result = Builder::create()
            ->writer(new PngWriter())
            ->data('EYE2WIN_INVITE:' . $token)
            ->size(200)
            ->margin(20)
            ->build();

        return new Response($result->getString(), 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => ResponseHeaderBag::DISPOSITION_INLINE,
        ]);
    }

    #[Route('/community/invites/{token}/open', name: 'community_invite_open', methods: ['GET'])]
    public function open(
        string $token,
        ChannelInviteRepository $inviteRepo,
        ChannelMemberRepository $memberRepo,
        ChannelJoinRequestRepository $reqRepo,
        UserRepository $userRepo,
        EntityManagerInterface $em
    ): Response {
        // not logged -> redirect to login then come back here
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login', [
                '_target_path' => $this->generateUrl('community_invite_open', ['token' => $token]),
            ]);
        }

        $invite = $inviteRepo->findOneBy(['token' => $token]);
        if (!$invite || !$invite->isActive()) {
            throw $this->createNotFoundException('Invite not found or inactive.');
        }

        // expiry
        if ($invite->getExpiresAt() && $invite->getExpiresAt() < new \DateTimeImmutable()) {
            throw $this->createAccessDeniedException('Invite expired.');
        }

        // max uses
        if ($invite->getMaxUses() !== null && $invite->getUses() !== null && $invite->getUses() >= $invite->getMaxUses()) {
            throw $this->createAccessDeniedException('Invite max uses reached.');
        }

        $channel = $invite->getChannel();
        $me = $this->getUser();

        // already creator or member -> just open channel
        if ($channel->getCreatedBy() === $me->getUserIdentifier()
            || $memberRepo->findOneBy(['channel' => $channel, 'user' => $me])
        ) {
            return $this->redirectToRoute('community_channels_show', ['id' => $channel->getId()]);
        }

        // consume one use
        $invite->setUses(($invite->getUses() ?? 0) + 1);

        if ($invite->getMode() === 'auto_join') {
            $member = new ChannelMember();
            $member->setChannel($channel);
            $member->setUser($me);
            $member->setJoinedAt(new \DateTimeImmutable());
            $em->persist($member);

            $em->flush();
            return $this->redirectToRoute('community_channels_show', ['id' => $channel->getId()]);
        }

        // request_only -> create join request + notify creator
        $existing = $reqRepo->findOneBy([
            'channel' => $channel,
            'requester' => $me,
            'status' => 'pending',
        ]);

        if (!$existing) {
            $jr = new ChannelJoinRequest();
            $jr->setChannel($channel);
            $jr->setRequester($me);
            $jr->setStatus('pending');
            $jr->setRequestedAt(new \DateTimeImmutable());
            $em->persist($jr);

            // notify creator
            $creatorKey = trim((string) $channel->getCreatedBy());
            $creator = $userRepo->findOneBy(['email' => $creatorKey]) ?: $userRepo->findOneBy(['username' => $creatorKey]);

            if ($creator) {
                $notif = new Notification();
                $notif->setUser($creator);
                $notif->setType(NotificationType::CHANNEL_ACCESS_REQUEST);
                $notif->setMessage(sprintf('%s requested access to "%s" via QR invite', $me->getUserIdentifier(), $channel->getName()));
                $notif->setLink($this->generateUrl('community_my_channel_requests'));
                $em->persist($notif);
            }
        }

        $em->flush();

        return $this->redirectToRoute('community_channels_show', ['id' => $channel->getId()]);
    }
}