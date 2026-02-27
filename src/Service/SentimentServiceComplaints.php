<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Sentiment Analysis Service — Complaints module
 *
 * Uses HuggingFace Inference API (free tier) with keyword fallback.
 * Model : cardiffnlp/twitter-roberta-base-sentiment-latest
 * Limits: ~30 000 req/month, aucune CB requise
 *
 * Setup : ajouter dans .env.local
 *   HUGGINGFACE_API_KEY=hf_xxxxxxxxxxxxxxxxxxxxxxxxxxxx
 *   (clé gratuite sur https://huggingface.co/settings/tokens)
 *
 * Sans clé → fallback par mots-clés activé automatiquement.
 */
class SentimentServiceComplaints
{
    private const HF_API_URL = 'https://api-inference.huggingface.co/models/cardiffnlp/twitter-roberta-base-sentiment-latest';

    // Seuils de confiance pour suggérer une priorité
    private const URGENT_THRESHOLD   = 0.75; // Très négatif → URGENT
    private const HIGH_THRESHOLD     = 0.55; // Négatif      → HIGH

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface     $logger,
        private readonly string              $huggingFaceApiKey = ''
    ) {}

    /**
     * Analyse le sentiment d'un texte de réclamation.
     *
     * Retourne :
     * [
     *   'label'               => 'NEGATIVE' | 'NEUTRAL' | 'POSITIVE',
     *   'text_label'          => 'Negative' | 'Neutral' | 'Positive',
     *   'score'               => float 0.0–1.0,
     *   'score_percent'       => int 0–100,
     *   'raw'                 => array  (labels bruts de l'API),
     *   'emoji'               => '😡' | '😐' | '😊',
     *   'badge_class'         => 'danger' | 'warning' | 'success',
     *   'priority_suggestion' => 'URGENT' | 'HIGH' | null,
     *   'source'              => 'api' | 'fallback',
     * ]
     */
    public function analyse(string $text): array
    {
        $text = trim($text);

        if ($text === '') {
            return $this->buildResult('NEUTRAL', 0.5, [], 'fallback');
        }

        // Tentative API HuggingFace
        if ($this->huggingFaceApiKey !== '') {
            try {
                $result = $this->callHuggingFace($text);
                if ($result !== null) {
                    return $result;
                }
            } catch (\Throwable $e) {
                $this->logger->warning('[SentimentServiceComplaints] API HuggingFace indisponible, fallback activé', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Fallback mots-clés (fonctionne sans connexion ni clé)
        return $this->keywordFallback($text);
    }

    // ──────────────────────────────────────────────────────────────
    // Privé : appel API HuggingFace
    // ──────────────────────────────────────────────────────────────

    private function callHuggingFace(string $text): ?array
    {
        // Le modèle accepte 512 caractères max
        $payload = mb_substr($text, 0, 512);

        $response = $this->httpClient->request('POST', self::HF_API_URL, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->huggingFaceApiKey,
                'Content-Type'  => 'application/json',
            ],
            'json'    => ['inputs' => $payload],
            'timeout' => 8,
        ]);

        $statusCode = $response->getStatusCode();

        // Modèle en cours de chargement → fallback
        if ($statusCode === 503) {
            $this->logger->info('[SentimentServiceComplaints] Modèle en chargement, fallback utilisé');
            return null;
        }

        if ($statusCode !== 200) {
            throw new \RuntimeException("[SentimentServiceComplaints] HTTP {$statusCode}");
        }

        $data = $response->toArray();

        // Format réponse : [[{"label":"positive","score":0.9}, ...]]
        $labels = $data[0] ?? [];
        if (empty($labels)) {
            return null;
        }

        usort($labels, fn($a, $b) => $b['score'] <=> $a['score']);
        $top = $labels[0];

        $label = match (strtolower($top['label'])) {
            'positive', 'pos', 'label_2' => 'POSITIVE',
            'negative', 'neg', 'label_0' => 'NEGATIVE',
            default                       => 'NEUTRAL',
        };

        return $this->buildResult($label, (float) $top['score'], $labels, 'api');
    }

    // ──────────────────────────────────────────────────────────────
    // Privé : fallback par mots-clés
    // ──────────────────────────────────────────────────────────────

    private function keywordFallback(string $text): array
    {
        $lower = mb_strtolower($text);

        $negativeWords = [
            'angry', 'furious', 'outraged', 'disgusted', 'hate', 'terrible', 'horrible',
            'awful', 'pathetic', 'ridiculous', 'unacceptable', 'disgraceful', 'incompetent',
            'useless', 'broken', 'scam', 'fraud', 'steal', 'stole', 'cheating', 'cheat',
            'lied', 'lie', 'lies', 'ripped off', 'never works', 'doesn\'t work', 'not working',
            'failed', 'failure', 'worst', 'bad', 'poor', 'urgent', 'immediately', 'asap',
            'lawsuit', 'sue', 'legal', 'report', 'banned', 'unfair', 'stolen', 'lost',
            'frustrated', 'disappointed', 'upset', 'annoyed', 'can\'t believe',
            'wtf', 'wth', 'damn', 'hell', 'crap',
        ];

        $positiveWords = [
            'thank', 'thanks', 'please', 'appreciate', 'help', 'kind', 'great', 'good',
            'excellent', 'wonderful', 'fantastic', 'amazing', 'love', 'happy', 'satisfied',
            'resolved', 'fixed', 'working', 'perfect', 'awesome', 'nice',
        ];

        $negScore = 0;
        $posScore = 0;

        foreach ($negativeWords as $word) {
            if (str_contains($lower, $word)) {
                $negScore++;
            }
        }
        foreach ($positiveWords as $word) {
            if (str_contains($lower, $word)) {
                $posScore++;
            }
        }

        // Les points d'exclamation amplifient la négativité
        $negScore += (int) (substr_count($text, '!') / 2);

        // Mots en MAJUSCULES (cris)
        preg_match_all('/\b[A-Z]{3,}\b/', $text, $capsMatches);
        $negScore += count($capsMatches[0]);

        if ($negScore > $posScore + 1) {
            $confidence = min(0.5 + $negScore * 0.08, 0.95);
            return $this->buildResult('NEGATIVE', $confidence, [], 'fallback');
        }

        if ($posScore > $negScore) {
            $confidence = min(0.5 + $posScore * 0.06, 0.90);
            return $this->buildResult('POSITIVE', $confidence, [], 'fallback');
        }

        return $this->buildResult('NEUTRAL', 0.55, [], 'fallback');
    }

    // ──────────────────────────────────────────────────────────────
    // Privé : construction du tableau résultat normalisé
    // ──────────────────────────────────────────────────────────────

    private function buildResult(string $label, float $score, array $raw, string $source): array
    {
        $emoji      = match ($label) { 'NEGATIVE' => '😡', 'POSITIVE' => '😊', default => '😐' };
        $badgeClass = match ($label) { 'NEGATIVE' => 'danger', 'POSITIVE' => 'success', default => 'warning' };
        $textLabel  = match ($label) { 'NEGATIVE' => 'Negative', 'POSITIVE' => 'Positive', default => 'Neutral' };

        $prioritySuggestion = null;
        if ($label === 'NEGATIVE') {
            if ($score >= self::URGENT_THRESHOLD) {
                $prioritySuggestion = 'URGENT';
            } elseif ($score >= self::HIGH_THRESHOLD) {
                $prioritySuggestion = 'HIGH';
            }
        }

        return [
            'label'               => $label,
            'text_label'          => $textLabel,
            'score'               => round($score, 3),
            'score_percent'       => (int) round($score * 100),
            'raw'                 => $raw,
            'emoji'               => $emoji,
            'badge_class'         => $badgeClass,
            'priority_suggestion' => $prioritySuggestion,
            'source'              => $source,
        ];
    }
}
