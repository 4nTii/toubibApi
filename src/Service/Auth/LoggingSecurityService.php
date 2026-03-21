<?php

namespace App\Service\Auth;

use App\Repository\LoggingAttemptRepository;

class LoggingSecurityService
{
    private LoggingAttemptRepository $loggingAttemptRepository;

    private int $maxAttemptPerUser;
    private int $maxTimeLimitPerUser;
    private int $maxAttemptPerIp;
    private int $maxTimeLimitPerIp;

    public function __construct(
        LoggingAttemptRepository $loggingAttemptRepository,
        int $max_attempt_per_user,
        int $max_time_limit_per_user,
        int $max_attempt_per_ip,
        int $max_time_limit_per_ip
    ) {
        $this->loggingAttemptRepository = $loggingAttemptRepository;

        $this->maxAttemptPerUser = $max_attempt_per_user;
        $this->maxTimeLimitPerUser = $max_time_limit_per_user;
        $this->maxAttemptPerIp = $max_attempt_per_ip;
        $this->maxTimeLimitPerIp = $max_time_limit_per_ip;
    }

    /**
     * Check if login attempt is allowed
     * @param $ipAddress 
     * @param $email
     * 
     * @return bool
     */
    public function verifyLoggingAbility(string $ipAddress, ?string $email): bool
    {
        $ipMinutes = (int) ceil($this->maxTimeLimitPerIp / 60);
        $userMinutes = (int) ceil($this->maxTimeLimitPerUser / 60);

        $ipAttempts = $this->loggingAttemptRepository
            ->countRecentAttemptsByIpAddress($ipAddress, $ipMinutes);

        if ($ipAttempts >= $this->maxAttemptPerIp) {
            return false;
        }

        if ($email) {
            $userAttempts = $this->loggingAttemptRepository
                ->countRecentAttemptsByEmail($email, $userMinutes);

            if ($userAttempts >= $this->maxAttemptPerUser) {
                return false;
            }
        }

        return true;
    }
}
