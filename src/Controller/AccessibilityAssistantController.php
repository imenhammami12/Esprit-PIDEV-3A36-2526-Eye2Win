<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AccessibilityAssistantController extends AbstractController
{
    public function __construct(private HttpClientInterface $httpClient) {}

    #[Route('/api/assistant', name: 'api_assistant', methods: ['POST'])]
    public function chat(Request $request): JsonResponse
    {
        try {
            $data    = json_decode($request->getContent(), true) ?? [];
            $history = $data['history'] ?? [];
            $lang    = isset($data['lang']) && in_array($data['lang'], ['fr', 'en']) ? $data['lang'] : 'en'; // Default: English

            if (empty($history)) {
                return $this->json(['error' => 'No message provided'], 400);
            }

            $apiKey = $this->getParameter('groq_api_key');
            if (empty($apiKey)) {
                return $this->json(['error' => 'GROQ_API_KEY is empty — check your .env.local'], 500);
            }

            $systemInstruction = $lang === 'fr'
                ? $this->getSystemPromptFr()
                : $this->getSystemPromptEn();

            $messages = [
                ['role' => 'system', 'content' => $systemInstruction],
            ];

            foreach ($history as $m) {
                $messages[] = [
                    'role'    => $m['role'] === 'assistant' ? 'assistant' : 'user',
                    'content' => $m['content'],
                ];
            }

            $response = $this->httpClient->request('POST',
                'https://api.groq.com/openai/v1/chat/completions',
                [
                    'verify_peer' => false,
                    'verify_host' => false,
                    'timeout'     => 15,
                    'headers'     => [
                        'Content-Type'  => 'application/json',
                        'Authorization' => 'Bearer ' . $apiKey,
                    ],
                    'json' => [
                        'model'       => 'llama-3.1-8b-instant',
                        'max_tokens'  => 500,
                        'temperature' => 0.7,
                        'messages'    => $messages,
                    ],
                ]
            );

            $statusCode = $response->getStatusCode();
            $rawBody    = $response->getContent(false);

            if ($statusCode !== 200) {
                return $this->json([
                    'error' => "Groq HTTP {$statusCode}: " . substr($rawBody, 0, 300),
                ], 500);
            }

            $result = json_decode($rawBody, true);

            if (isset($result['error'])) {
                return $this->json([
                    'error' => 'Groq error: ' . ($result['error']['message'] ?? 'unknown'),
                ], 500);
            }

            $text = $result['choices'][0]['message']['content']
                ?? ($lang === 'fr' ? 'Désolé, pas de réponse.' : 'Sorry, no response.');

            return $this->json(['reply' => $text]);

        } catch (\Exception $e) {
            return $this->json([
                'error' => get_class($e) . ': ' . $e->getMessage(),
                'file'  => basename($e->getFile()) . ':' . $e->getLine(),
            ], 500);
        }
    }

    private function getSystemPromptFr(): string
    {
        return "Tu es l'assistant d'accessibilité d'EyeTwin, une plateforme eSport compétitive.
Tu aides les utilisateurs (y compris personnes handicapées) à naviguer et utiliser le site.
Réponds TOUJOURS en français, de façon concise et bienveillante.

RÈGLE CRITIQUE: Pour naviguer, tu DOIS utiliser UNIQUEMENT ces URLs exactes, sans jamais les modifier:

[NAVIGATE:/]                        → Page d'accueil
[NAVIGATE:/planning]                → Planning des sessions
[NAVIGATE:/my-sessions]             → Mes sessions
[NAVIGATE:/tournaments/landing]     → Page tournois
[NAVIGATE:/tournaments/]            → Liste des tournois
[NAVIGATE:/upload]                  → Uploader une vidéo
[NAVIGATE:/teams/]                  → Teams
[NAVIGATE:/channels]                → Communauté / Channels
[NAVIGATE:/live/]                   → Live streams
[NAVIGATE:/coins/]                  → Coins / Boutique
[NAVIGATE:/profile/]                → Mon profil
[NAVIGATE:/profile/edit]            → Modifier mon profil
[NAVIGATE:/profile/statistics]      → Mes statistiques
[NAVIGATE:/profile/2fa]             → Authentification 2FA
[NAVIGATE:/complaints/]             → Support / Réclamations
[NAVIGATE:/login]                   → Connexion
[NAVIGATE:/register]                → Inscription
[NAVIGATE:/dashboard]               → Dashboard
[NAVIGATE:/profile/apply-coach]     → Devenir coach
[NAVIGATE:/forgot-password]         → Mot de passe oublié

Commandes d'accessibilité disponibles:
[CONTRASTE]      → activer/désactiver le contraste élevé
[GRAND_TEXTE]    → agrandir le texte
[DYSLEXIE]       → activer la police lisibilité
[LECTURE]        → lire la page à voix haute
[STOP_LECTURE]   → arrêter la lecture
[GUIDE]          → activer le guide de lecture
[FOCUS]          → activer le mode focus clavier

IMPORTANT: Ne jamais inventer une URL. Utilise UNIQUEMENT les URLs listées ci-dessus.
Si l'utilisateur veut aller quelque part, inclus la commande [NAVIGATE:/url] dans ta réponse.";
    }

    private function getSystemPromptEn(): string
    {
        return "You are the accessibility assistant for EyeTwin, a competitive eSport platform.
You help users (including people with disabilities) navigate and use the site.
ALWAYS respond in English, concisely and kindly.

CRITICAL RULE: For navigation, you MUST use ONLY these exact URLs, never modify them:

[NAVIGATE:/]                        → Home page
[NAVIGATE:/planning]                → Sessions planning
[NAVIGATE:/my-sessions]             → My sessions
[NAVIGATE:/tournaments/landing]     → Tournaments page
[NAVIGATE:/tournaments/]            → Tournaments list
[NAVIGATE:/upload]                  → Upload a video
[NAVIGATE:/teams/]                  → Teams
[NAVIGATE:/channels]                → Community / Channels
[NAVIGATE:/live/]                   → Live streams
[NAVIGATE:/coins/]                  → Coins / Shop
[NAVIGATE:/profile/]                → My profile
[NAVIGATE:/profile/edit]            → Edit profile
[NAVIGATE:/profile/statistics]      → My statistics
[NAVIGATE:/profile/2fa]             → 2FA settings
[NAVIGATE:/complaints/]             → Support / Complaints
[NAVIGATE:/login]                   → Login
[NAVIGATE:/register]                → Register
[NAVIGATE:/dashboard]               → Dashboard
[NAVIGATE:/profile/apply-coach]     → Become a coach
[NAVIGATE:/forgot-password]         → Forgot password

Available accessibility commands:
[CONTRASTE]      → toggle high contrast mode
[GRAND_TEXTE]    → enlarge text
[DYSLEXIE]       → enable readability font
[LECTURE]        → read the page aloud
[STOP_LECTURE]   → stop reading
[GUIDE]          → enable reading guide
[FOCUS]          → enable keyboard focus mode

IMPORTANT: Never invent a URL. Use ONLY the URLs listed above.
If the user wants to go somewhere, include the [NAVIGATE:/url] command in your response.";
    }
}