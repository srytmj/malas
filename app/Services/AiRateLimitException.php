<?php

namespace App\Services;

/**
 * Dilempar saat provider AI (Gemini/OpenAI/Claude) membalas HTTP 429 — biasanya kuota
 * free-tier habis. Ditangani terpisah dari error lain supaya bisa jatuh ke fallback text
 * tanpa memotong kuota generate ulang manual user, dan supaya log-nya nggak kelihatan
 * seperti bug aplikasi.
 */
class AiRateLimitException extends \RuntimeException {}
