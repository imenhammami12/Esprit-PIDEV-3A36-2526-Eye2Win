<?php

namespace App\Service;

use App\Entity\Channel;
use App\Repository\ChannelMemberRepository;
use Symfony\Component\Security\Core\User\UserInterface;

class ChannelAccessService
{
    public function __construct(private ChannelMemberRepository $members)
    {
    }

    public function isCreator(Channel $channel, ?UserInterface $user): bool
    {
        return $user && $user->getUserIdentifier() === $channel->getCreatedBy();
    }

    public function isMember(Channel $channel, ?UserInterface $user): bool
    {
        if (!$user) return false;

        return (bool)$this->members->findOneBy([
            'channel' => $channel,
            'user' => $user,
        ]);
    }

    public function canAccess(Channel $channel, ?UserInterface $user): bool
    {
        if ($channel->getType() === Channel::TYPE_PUBLIC) return true;
        return $this->isCreator($channel, $user) || $this->isMember($channel, $user);
    }
}