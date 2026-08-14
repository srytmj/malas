<?php

namespace App\Services;

use App\Models\AiSetting;
use Illuminate\Support\Facades\Http;

class AiFunfactService
{
    /**
     * @param  array{top_genres: array<int, array{genre: string, count: int, percent: float}>, comparison: array{genre: string, multiplier: float}|null}  $context
     */
    public function generate(array $context): string
    {
        $setting = AiSetting::first();

        // Provider 'puter' cuma bisa dipanggil dari browser (lihat lib/puter.ts) — server nggak
        // pernah generate langsung untuknya, jadi jatuh ke fallback kalau somehow ke-panggil di sini.
        if (! $setting || $setting->provider === 'puter' || blank($setting->api_key)) {
            return $this->fallbackText($context);
        }

        $prompt = $this->buildPrompt($context);

        return match ($setting->provider) {
            'openai' => $this->viaOpenAi($setting->api_key, $prompt),
            'claude' => $this->viaClaude($setting->api_key, $prompt),
            default => $this->viaGemini($setting->api_key, $prompt),
        };
    }

    public function buildPrompt(array $context): string
    {
        $topGenres = collect($context['top_genres'])
            ->map(fn ($g) => "{$g['genre']} ({$g['percent']}%)")
            ->implode(', ');

        $comparisonLine = $context['comparison']
            ? "Dibanding rata-rata pengguna lain, dia {$context['comparison']['multiplier']}x lebih sering mengoleksi genre {$context['comparison']['genre']}."
            : '';

        return <<<PROMPT
            Kamu adalah asisten yang membuat "funfact" singkat dan menyenangkan tentang selera genre koleksi manga/light novel seorang pengguna aplikasi bernama Malas.

            Data genre koleksi pengguna ini (persentase dari total genre di koleksinya): {$topGenres}.
            {$comparisonLine}

            Tulis SATU funfact dalam Bahasa Indonesia, gaya santai dan playful, maksimal 2 kalimat pendek, boleh pakai emoji secukupnya. Jangan gunakan format markdown. Langsung tulis funfact-nya saja tanpa basa-basi pembuka.
            PROMPT;
    }

    public function fallbackText(array $context): string
    {
        $top = $context['top_genres'][0] ?? null;

        if (! $top) {
            return 'Koleksimu masih terlalu sedikit genre untuk disimpulkan — tambah lagi biar makin kelihatan seleramu!';
        }

        $text = "Genre favoritmu adalah {$top['genre']} — {$top['percent']}% dari koleksimu!";

        if ($context['comparison']) {
            $text .= " Kamu {$context['comparison']['multiplier']}x lebih suka {$context['comparison']['genre']} dibanding rata-rata pengguna lain.";
        }

        return $text;
    }

    private function viaGemini(string $apiKey, string $prompt): string
    {
        $response = Http::timeout(20)
            ->retry(2, 500, throw: false)
            ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$apiKey}", [
                'contents' => [
                    ['parts' => [['text' => $prompt]]],
                ],
            ]);

        $this->assertSuccessful($response, 'Gemini');

        $text = data_get($response->json(), 'candidates.0.content.parts.0.text');

        return $this->cleanText($text);
    }

    private function viaOpenAi(string $apiKey, string $prompt): string
    {
        $response = Http::timeout(20)
            ->retry(2, 500, throw: false)
            ->withToken($apiKey)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

        $this->assertSuccessful($response, 'OpenAI');

        $text = data_get($response->json(), 'choices.0.message.content');

        return $this->cleanText($text);
    }

    private function viaClaude(string $apiKey, string $prompt): string
    {
        $response = Http::timeout(20)
            ->retry(2, 500, throw: false)
            ->withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
            ])
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => 'claude-haiku-4-5-20251001',
                'max_tokens' => 300,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

        $this->assertSuccessful($response, 'Claude');

        $text = data_get($response->json(), 'content.0.text');

        return $this->cleanText($text);
    }

    private function assertSuccessful($response, string $provider): void
    {
        if ($response->status() === 429) {
            throw new AiRateLimitException("Rate limit tercapai untuk {$provider} (HTTP 429): {$response->body()}");
        }

        if (! $response->successful()) {
            throw new \Exception("Gagal generate funfact dari {$provider} (HTTP {$response->status()}): {$response->body()}");
        }
    }

    private function cleanText(?string $text): string
    {
        $text = trim((string) $text);

        if ($text === '') {
            throw new \Exception('Respons AI kosong.');
        }

        return $text;
    }
}
