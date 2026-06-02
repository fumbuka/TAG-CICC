<?php

namespace App\Http\Middleware;

use App\Models\SiteVisit;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class TrackPublicSiteVisit
{
    private const COOKIE_NAME = 'tag_cicc_site_visitor';

    private const PUBLIC_ROUTE_NAMES = [
        'home',
        'public.about',
        'public.ministries',
        'public.calendar',
        'public.weekly-leadership',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->shouldTrack($request, $response)) {
            return $response;
        }

        $visitorId = $this->visitorId($request);
        $needsCookie = $request->cookies->get(self::COOKIE_NAME) !== $visitorId;

        if ($needsCookie) {
            $response->headers->setCookie($this->visitorCookie($request, $visitorId));
        }

        $this->track($request, $visitorId);

        return $response;
    }

    private function shouldTrack(Request $request, Response $response): bool
    {
        if (! $request->isMethod('GET') || $response->getStatusCode() >= 400) {
            return false;
        }

        $routeName = $request->route()?->getName();
        if (! in_array($routeName, self::PUBLIC_ROUTE_NAMES, true)) {
            return false;
        }

        if ($this->isLikelyBot((string) $request->userAgent())) {
            return false;
        }

        return $this->siteVisitsTableExists();
    }

    private function track(Request $request, string $visitorId): void
    {
        try {
            SiteVisit::create([
                'visitor_hash' => $this->hash($visitorId),
                'ip_hash' => $request->ip() ? $this->hash((string) $request->ip()) : null,
                'user_agent_hash' => $request->userAgent() ? $this->hash((string) $request->userAgent()) : null,
                'route_name' => $request->route()?->getName(),
                'path' => '/'.ltrim($request->path(), '/'),
                'referrer_host' => $this->referrerHost((string) $request->headers->get('referer', '')),
                'visited_at' => now(),
            ]);
        } catch (Throwable) {
            // Never let analytics tracking block the public website.
        }
    }

    private function visitorId(Request $request): string
    {
        $visitorId = $request->cookies->get(self::COOKIE_NAME);

        return is_string($visitorId) && Str::isUuid($visitorId)
            ? $visitorId
            : (string) Str::uuid();
    }

    private function visitorCookie(Request $request, string $visitorId): Cookie
    {
        return cookie(
            self::COOKIE_NAME,
            $visitorId,
            60 * 24 * 365 * 2,
            '/',
            null,
            $request->isSecure(),
            true,
            false,
            'Lax',
        );
    }

    private function siteVisitsTableExists(): bool
    {
        return Schema::hasTable('site_visits');
    }

    private function hash(string $value): string
    {
        return hash_hmac('sha256', $value, (string) config('app.key'));
    }

    private function referrerHost(string $referrer): ?string
    {
        $host = parse_url($referrer, PHP_URL_HOST);

        return is_string($host) && $host !== '' ? Str::limit($host, 255, '') : null;
    }

    private function isLikelyBot(string $userAgent): bool
    {
        if ($userAgent === '') {
            return false;
        }

        return Str::of($userAgent)->lower()->contains([
            'bot',
            'crawler',
            'spider',
            'slurp',
            'ahrefs',
            'semrush',
            'bingpreview',
        ]);
    }
}
