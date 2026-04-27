<?php

/**
 * Rate Limiting
 */

class RateLimiter
{
    private static PDO $db;

    public static function init(PDO $db): void
    {
        self::$db = $db;
    }

    public static function check(string $identifier, int $maxAttempts = 5, int $windowSeconds = 900): bool
    {
        $window = date('Y-m-d H:i:s', time() - $windowSeconds);
        
        $stmt = self::$db->prepare("DELETE FROM rate_limits WHERE created_at < ?");
        $stmt->execute([$window]);
        
        $stmt = self::$db->prepare("SELECT COUNT(*) FROM rate_limits WHERE identifier = ? AND created_at > ?");
        $stmt->execute([$identifier, $window]);
        $count = (int)$stmt->fetchColumn();

        return $count < $maxAttempts;
    }

    public static function hit(string $identifier): void
    {
        $stmt = self::$db->prepare("INSERT INTO rate_limits (identifier, ip_address) VALUES (?, ?)");
        $stmt->execute([$identifier, $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
    }

    public static function remaining(string $identifier, int $maxAttempts = 5, int $windowSeconds = 900): int
    {
        $window = date('Y-m-d H:i:s', time() - $windowSeconds);
        $stmt = self::$db->prepare("SELECT COUNT(*) FROM rate_limits WHERE identifier = ? AND created_at > ?");
        $stmt->execute([$identifier, $window]);
        return max(0, $maxAttempts - (int)$stmt->fetchColumn());
    }
}