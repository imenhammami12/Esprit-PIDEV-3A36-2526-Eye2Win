<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class AssistantController extends AbstractController
{
    #[Route('/api/assistant', name: 'api_assistant', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $data    = json_decode($request->getContent(), true);
        $history = $data['history'] ?? [];
        $last    = end($history);
        $userMsg = is_array($last) ? ($last['content'] ?? '') : '';

        // Simple rule-based responses (no external API needed)
        $reply = $this->getReply(strtolower($userMsg));

        return $this->json(['reply' => $reply]);
    }

    private function getReply(string $msg): string
    {
        if (str_contains($msg, 'planning') || str_contains($msg, 'plan')) {
            return 'You can access the Planning section from the navigation menu. [NAVIGATE:/planning]';
        }
        if (str_contains($msg, 'tournament') || str_contains($msg, 'tournoi')) {
            return 'Tournaments are available in the Tournois section. [NAVIGATE:/tournaments/landing]';
        }
        if (str_contains($msg, 'community') || str_contains($msg, 'channel')) {
            return 'The Community section lets you join channels and chat. [NAVIGATE:/channels]';
        }
        if (str_contains($msg, 'team')) {
            return 'You can manage your teams in the Teams section. [NAVIGATE:/teams/]';
        }
        if (str_contains($msg, 'profile')) {
            return 'Your profile is accessible from the top right menu. [NAVIGATE:/profile/]';
        }
        if (str_contains($msg, 'contrast')) {
            return 'I will enable high contrast mode for you. [CONTRASTE]';
        }
        if (str_contains($msg, 'text') || str_contains($msg, 'font size') || str_contains($msg, 'bigger')) {
            return 'I will increase the text size for you. [GRAND_TEXTE]';
        }
        if (str_contains($msg, 'read') || str_contains($msg, 'speak') || str_contains($msg, 'listen')) {
            return 'Starting page reading now. [LECTURE]';
        }
        if (str_contains($msg, 'stop')) {
            return 'Stopping the reading. [STOP_LECTURE]';
        }
        if (str_contains($msg, 'guide') || str_contains($msg, 'line')) {
            return 'Enabling the reading guide to help you follow along. [GUIDE]';
        }
        if (str_contains($msg, 'login') || str_contains($msg, 'sign in')) {
            return 'You can log in from the login page. [NAVIGATE:/login]';
        }
        if (str_contains($msg, 'register') || str_contains($msg, 'sign up')) {
            return 'Create your account on the registration page. [NAVIGATE:/register]';
        }
        if (str_contains($msg, 'complaint') || str_contains($msg, 'support') || str_contains($msg, 'help')) {
            return 'For support, you can open a ticket in the complaints section. [NAVIGATE:/complaints/]';
        }
        if (str_contains($msg, 'coin') || str_contains($msg, 'balance')) {
            return 'You can check and manage your coins here. [NAVIGATE:/coins/]';
        }
        if (str_contains($msg, 'hello') || str_contains($msg, 'hi') || str_contains($msg, 'hey')) {
            return 'Hello! I\'m here to help you navigate EyeTwin. You can ask me about any section of the platform or request accessibility features like contrast, text size, or reading assistance.';
        }

        return 'I can help you navigate EyeTwin or enable accessibility features. Try asking about: Planning, Tournaments, Community, Teams, Profile, or ask me to enable Contrast, Read aloud, or Guide mode.';
    }
}