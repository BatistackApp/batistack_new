<?php

namespace App\Support;

use Spatie\Browsershot\Browsershot as BaseBrowsershot;
use Spatie\Browsershot\Exceptions\FileUrlNotAllowed;

class Browsershot extends BaseBrowsershot
{
    /**
     * PHP's FILTER_VALIDATE_URL rejette les hôtes contenant un underscore
     * (ex. https://batistack_new.test), alors que "_" est un caractère
     * unreserved autorisé par la RFC 3986. Les URL générées par route()
     * sont alors refusées à tort par Browsershot. On les accepte si elles
     * deviennent valides une fois les underscores de l'hôte supprimés.
     */
    public function setUrl(string $url): static
    {
        $url = trim($url);

        if (filter_var($url, FILTER_VALIDATE_URL) === false && ! $this->isValidWithoutHostUnderscores($url)) {
            throw FileUrlNotAllowed::urlCannotBeParsed($url);
        }

        foreach ($this->unsafeProtocols as $unsupportedProtocol) {
            if (str_starts_with(strtolower($url), $unsupportedProtocol)) {
                throw FileUrlNotAllowed::make();
            }
        }

        $this->url = $url;
        $this->html = '';

        return $this;
    }

    private function isValidWithoutHostUnderscores(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (! is_string($host) || ! str_contains($host, '_')) {
            return false;
        }

        return filter_var(str_replace($host, str_replace('_', '', $host), $url), FILTER_VALIDATE_URL) !== false;
    }
}
