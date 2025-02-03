<?php

namespace App;

use Psr\Http\Message\ServerRequestInterface as Request;

class IpResolver
{
    public function getClientIp(Request $request, array $trustedProxies): ?string
    {
        $serverParams = $request->getServerParams();
        $remoteIp = $serverParams['REMOTE_ADDR'] ?? null;

        if ($remoteIp && in_array($remoteIp, $trustedProxies, true)) {
            $forwardedFor = $serverParams['HTTP_X_FORWARDED_FOR'] ?? '';

            if (! empty($forwardedFor)) {
                $ips = array_map('trim', explode(',', $forwardedFor));

                foreach ($ips as $ip) {
                    if ($this->isValidPublicIp($ip)) {
                        return $ip;
                    }
                }
            }
        }

        return $this->isValidPublicIp($remoteIp) ? $remoteIp : null;
    }

    private function isValidPublicIp(?string $ip): bool
    {
        if (! $ip || ! filter_var($ip, FILTER_VALIDATE_IP,FILTER_FLAG_NO_RES_RANGE)) {
            return false;
        }
        return true;
    }
}