<?php

declare(strict_types=1);

namespace Eram\Ersal\Request;

/**
 * Shipping label returned from SupportsLabel::getLabel().
 *
 * Carriers return labels either as raw bytes (PDF/PNG/ZPL) or as a URL
 * pointing to a hosted copy — at least one of `$bytes` or `$url` is set.
 */
final class LabelResponse
{
    public function __construct(
        public readonly string $format,
        public readonly string $bytes = '',
        public readonly ?string $url = null,
    ) {}

    public function hasBytes(): bool
    {
        return $this->bytes !== '';
    }

    public function hasUrl(): bool
    {
        return $this->url !== null;
    }
}
