<?php

declare(strict_types=1);

namespace Avvertix\WhatLang;

/**
 * Restricts which languages the detector may return.
 *
 * Ported from whatlang-rs (src/core/filter_list.rs).
 */
final class FilterList
{
    /**
     * @param  array<string, true>|null  $codes  null means "everything is allowed"
     */
    private function __construct(
        private readonly ?array $codes,
        private readonly bool $allow,
    ) {}

    public static function all(): self
    {
        return new self(null, true);
    }

    /**
     * Only the given languages may be detected.
     *
     * @param  iterable<Lang>  $allowlist
     */
    public static function allow(iterable $allowlist): self
    {
        return new self(self::index($allowlist), true);
    }

    /**
     * Every language except the given ones may be detected.
     *
     * @param  iterable<Lang>  $denylist
     */
    public static function deny(iterable $denylist): self
    {
        return new self(self::index($denylist), false);
    }

    public function isAllowed(Lang $lang): bool
    {
        if ($this->codes === null) {
            return true;
        }

        return isset($this->codes[$lang->value]) === $this->allow;
    }

    /**
     * @param  iterable<Lang>  $langs
     * @return array<string, true>
     */
    private static function index(iterable $langs): array
    {
        $codes = [];

        foreach ($langs as $lang) {
            $codes[$lang->value] = true;
        }

        return $codes;
    }
}
