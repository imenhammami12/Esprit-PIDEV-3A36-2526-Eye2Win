<?php

namespace App\Service;

class SentimentAnalysisService
{
    private string $apiKey;
    private string $apiUrl = 'https://api-inference.huggingface.co/models/distilbert-base-uncased-finetuned-sst-2-english';

    public function __construct()
    {
        $this->apiKey = '';
    }

    /**
     * Analyzes the sentiment of a review using its text content and star rating.
     *
     * @param string $content  The review text
     * @param int    $rating   Star rating (1–5)
     * @return string  'positive', 'negative', or 'neutral'
     */
    public function analyze(string $content, int $rating): string
    {
        // Enrich the input with the star rating as additional context
        $ratingContext = match(true) {
            $rating >= 4 => 'I am very satisfied.',
            $rating === 3 => 'It was okay.',
            default        => 'I am not satisfied.',
        };

        $text = trim($content . ' ' . $ratingContext);

        $payload = json_encode(['inputs' => $text]);

        $ch = curl_init($this->apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT        => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $httpCode !== 200) {
            // Fallback: derive sentiment from rating alone if API fails
            return $this->fallbackFromRating($rating);
        }

        $data = json_decode($response, true);

        // HF returns: [[{"label":"POSITIVE","score":0.99},{"label":"NEGATIVE","score":0.01}]]
        if (!isset($data[0]) || !is_array($data[0])) {
            return $this->fallbackFromRating($rating);
        }

        // Find highest-score label
        $best = null;
        foreach ($data[0] as $item) {
            if ($best === null || $item['score'] > $best['score']) {
                $best = $item;
            }
        }

        if ($best === null) {
            return $this->fallbackFromRating($rating);
        }

        $label = strtolower($best['label']);

        // Map to our three categories
        if ($label === 'positive') {
            // If text is positive but rating is very low, call it neutral
            if ($rating <= 2) {
                return 'neutral';
            }
            return 'positive';
        }

        if ($label === 'negative') {
            // If text is negative but rating is very high, call it neutral
            if ($rating >= 4) {
                return 'neutral';
            }
            return 'negative';
        }

        return 'neutral';
    }

    private function fallbackFromRating(int $rating): string
    {
        if ($rating >= 4) return 'positive';
        if ($rating <= 2) return 'negative';
        return 'neutral';
    }
}