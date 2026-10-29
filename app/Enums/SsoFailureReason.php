<?php

namespace App\Enums;

enum SsoFailureReason: string
{
    case RateLimited = 'rate_limited';
    case MissingToken = 'missing_token';
    case InvalidOrExpiredToken = 'invalid_or_expired_token';
    case WrongTenant = 'wrong_tenant';
    case UserNotFound = 'user_not_found';
    case ReplayDetected = 'replay_detected';
}
