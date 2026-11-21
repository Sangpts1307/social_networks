<?php

namespace App\Services;

class ContentModerationService
{
    /**
     * Inspect post title and body for sensitive content, secret leaks, toxicity, and scam/spam.
     *
     * @return array{
     *     is_flagged: bool,
     *     should_auto_hide: bool,
     *     risk_score: float,
     *     reason_code: string,
     *     category: string,
     *     detail: string,
     *     matches: array<string>
     * }
     */
    public function inspect(?string $title, ?string $body): array
    {
        $text = trim(($title ?? '') . "\n" . ($body ?? ''));
        if ($text === '') {
            return $this->cleanResult();
        }

        // 1. Secrets & Credential Exposure (Highest priority - severe security risk)
        $secretCheck = $this->checkSecrets($text);
        if ($secretCheck['is_flagged']) {
            return $secretCheck;
        }

        // 2. Toxic, Profane & Harassment Content
        $toxicCheck = $this->checkToxicContent($text);
        if ($toxicCheck['is_flagged']) {
            return $toxicCheck;
        }

        // 3. Scam, Gambling & Phishing
        $scamCheck = $this->checkScamAndGambling($text);
        if ($scamCheck['is_flagged']) {
            return $scamCheck;
        }

        return $this->cleanResult();
    }

    /**
     * @return array{is_flagged: bool, should_auto_hide: bool, risk_score: float, reason_code: string, category: string, detail: string, matches: array<string>}
     */
    private function cleanResult(): array
    {
        return [
            'is_flagged' => false,
            'should_auto_hide' => false,
            'risk_score' => 0.0,
            'reason_code' => 'none',
            'category' => 'An toàn',
            'detail' => 'Nội dung an toàn, không phát hiện dấu hiệu vi phạm.',
            'matches' => [],
        ];
    }

    /**
     * Detect leaked API keys, tokens, private keys, database passwords.
     *
     * @return array{is_flagged: bool, should_auto_hide: bool, risk_score: float, reason_code: string, category: string, detail: string, matches: array<string>}
     */
    private function checkSecrets(string $text): array
    {
        $patterns = [
            // AWS Access Key ID
            'aws_key' => [
                'regex' => '/\bAKIA[0-9A-Z]{16}\b/',
                'desc' => 'AWS Access Key ID',
                'score' => 0.98,
            ],
            // Stripe, generic Live Secret Keys (e.g. SK_LIVE_98234871...)
            'live_secret_key' => [
                'regex' => '/(?i)\b(?:sk_live|sk_test)_[0-9a-zA-Z]{12,}\b/',
                'desc' => 'Secret / Live API Key',
                'score' => 0.96,
            ],
            // Query param or URL containing secret key (e.g. key=SK_LIVE_... or api_key=...)
            'url_api_key' => [
                'regex' => '/(?i)[?&](?:key|api_key|secret|token)=(?:SK_LIVE_|SK_TEST_|ghp_|eyJ)[a-zA-Z0-9_\-\.]{10,}/',
                'desc' => 'API Key trên tham số đường dẫn',
                'score' => 0.95,
            ],
            // GitHub Personal Access Token
            'github_token' => [
                'regex' => '/\b(?:ghp_[a-zA-Z0-9]{36}|github_pat_[a-zA-Z0-9_]{40,})\b/',
                'desc' => 'GitHub Personal Access Token',
                'score' => 0.97,
            ],
            // Private Keys (RSA, EC, OPENSSH, etc.)
            'private_key' => [
                'regex' => '/-----BEGIN (?:RSA |EC |OPENSSH |DSA )?PRIVATE KEY-----/',
                'desc' => 'Private Encryption Key',
                'score' => 0.99,
            ],
            // Database Connection URI with password
            'db_uri' => [
                'regex' => '/(?:mongodb(?:\+srv)?|postgres(?:ql)?|mysql|redis):\/\/[a-zA-Z0-9_\-\.]+:[a-zA-Z0-9_\-\.\@\$%!\*]{4,}@[a-zA-Z0-9_\-\.]+/i',
                'desc' => 'Chuỗi kết nối cơ sở dữ liệu chứa mật khẩu',
                'score' => 0.95,
            ],
            // Plaintext password assignment in code/config snippet
            'plaintext_password' => [
                'regex' => '/(?i)\b(?:password|passwd|mat_khau|matkhau)\s*[:=]\s*["\'][a-zA-Z0-9!@#\$%\^&\*\-_]{8,}["\']/',
                'desc' => 'Mật khẩu văn bản thô',
                'score' => 0.91,
            ],
            // Generic high-risk key assignment
            'generic_api_key' => [
                'regex' => '/(?i)\b(?:api_key|apikey|secret_key|app_secret|client_secret)\s*[:=]\s*["\']?[a-zA-Z0-9_\-\.]{24,}["\']?/',
                'desc' => 'Khóa bí mật API / Client Secret',
                'score' => 0.92,
            ],
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern['regex'], $text, $matches)) {
                $rawMatch = $matches[0];
                $masked = strlen($rawMatch) > 10
                    ? substr($rawMatch, 0, 8) . '...' . substr($rawMatch, -4)
                    : substr($rawMatch, 0, 4) . '***';

                return [
                    'is_flagged' => true,
                    'should_auto_hide' => true, // Sensitive secrets are auto-hidden to avoid leakage
                    'risk_score' => $pattern['score'],
                    'reason_code' => 'secret_exposure',
                    'category' => 'Lộ thông tin bảo mật',
                    'detail' => 'AI Guard phát hiện rủi ro bảo mật nghiêm trọng: ' . $pattern['desc'] . ' (' . $masked . ')',
                    'matches' => [$masked],
                ];
            }
        }

        return ['is_flagged' => false, 'should_auto_hide' => false, 'risk_score' => 0.0, 'reason_code' => '', 'category' => '', 'detail' => '', 'matches' => []];
    }

    /**
     * Detect toxic language, vulgar profanity, and harassment.
     *
     * @return array{is_flagged: bool, should_auto_hide: bool, risk_score: float, reason_code: string, category: string, detail: string, matches: array<string>}
     */
    private function checkToxicContent(string $text): array
    {
        $normalized = mb_strtolower($text, 'UTF-8');

        // High-severity toxic/profane phrases (Vietnamese & common English)
        $toxicPatterns = [
            '/\b(?:đ[ịi][tụ]|đụ má|địt mẹ|đụ mẹ|dmm|đcm|đmm|dcm|vcl|vcll|ccmm|clmm|lồn|buồi|đéo|thằng chó|con chó|ngu như chó|ngu như lợn|óc chó|đồ chó chết)\b/u',
            '/\b(?:fuck(?:ing)?|motherfucker|bitch|asshole|bastard|dickhead|bullshit)\b/i',
        ];

        foreach ($toxicPatterns as $pattern) {
            if (preg_match($pattern, $normalized, $matches)) {
                $word = $matches[0];
                return [
                    'is_flagged' => true,
                    'should_auto_hide' => false, // Send to moderation queue
                    'risk_score' => 0.88,
                    'reason_code' => 'toxic_content',
                    'category' => 'Ngôn từ xúc phạm / Độc hại',
                    'detail' => 'AI Guard phát hiện từ ngữ thô tục hoặc công kích cá nhân (' . $word . ').',
                    'matches' => [$word],
                ];
            }
        }

        // Harassment or threats of violence
        $harassmentPatterns = [
            '/\b(?:giết chết|chém chết|đánh chết|cho ăn đòn|bắn chết|tao sẽ giết|đe dọa tính mạng)\b/u',
            '/\b(?:i will kill you|i\'ll murder|threaten to kill)\b/i',
        ];

        foreach ($harassmentPatterns as $pattern) {
            if (preg_match($pattern, $normalized, $matches)) {
                $word = $matches[0];
                return [
                    'is_flagged' => true,
                    'should_auto_hide' => true, // Violent threats are auto-hidden
                    'risk_score' => 0.95,
                    'reason_code' => 'harassment',
                    'category' => 'Đe dọa / Quấy rối',
                    'detail' => 'AI Guard phát hiện nội dung mang tính đe dọa hoặc bạo lực (' . $word . ').',
                    'matches' => [$word],
                ];
            }
        }

        return ['is_flagged' => false, 'should_auto_hide' => false, 'risk_score' => 0.0, 'reason_code' => '', 'category' => '', 'detail' => '', 'matches' => []];
    }

    /**
     * Detect gambling, black credit scams, and spam keywords.
     *
     * @return array{is_flagged: bool, should_auto_hide: bool, risk_score: float, reason_code: string, category: string, detail: string, matches: array<string>}
     */
    private function checkScamAndGambling(string $text): array
    {
        $normalized = mb_strtolower($text, 'UTF-8');

        $gamblingKeywords = [
            '/\b(?:kubet|bet88|fb88|w88|fun88|sv388|nohu|kéo tài xỉu|nổ hũ đổi thưởng|đánh lô đề online|soi cầu xổ số|kèo cá độ bóng đá|nhà cái uy tín)\b/u',
            '/\b(?:vay tiền không cần cccd|vay nóng giải ngân ngay|vay lãi ngày|bốc bát họ)\b/u',
        ];

        foreach ($gamblingKeywords as $pattern) {
            if (preg_match($pattern, $normalized, $matches)) {
                $matched = $matches[0];
                return [
                    'is_flagged' => true,
                    'should_auto_hide' => false,
                    'risk_score' => 0.84,
                    'reason_code' => 'spam',
                    'category' => 'Cờ bạc / Lừa đảo / Spam',
                    'detail' => 'AI Guard phát hiện nội dung có dấu hiệu cờ bạc, lừa đảo hoặc spam (' . $matched . ').',
                    'matches' => [$matched],
                ];
            }
        }

        return ['is_flagged' => false, 'should_auto_hide' => false, 'risk_score' => 0.0, 'reason_code' => '', 'category' => '', 'detail' => '', 'matches' => []];
    }
}
