<?php

declare(strict_types=1);

namespace Avvertix\WhatLang;

/**
 * The outcome of a detection.
 *
 * Ported from whatlang-rs (src/core/info.rs).
 */
final class DetectionInfo
{
    private const RELIABLE_CONFIDENCE_THRESHOLD = 0.9;

    public function __construct(
        public readonly Script $script,
        public readonly Lang $lang,
        public readonly float $confidence,
    ) {}

    /**
     * Whether the confidence is high enough to act on.
     */
    public function isReliable(): bool
    {
        return $this->confidence > self::RELIABLE_CONFIDENCE_THRESHOLD;
    }

    /**
     * @return array{lang: string, script: string, confidence: float, reliable: bool}
     */
    public function toArray(): array
    {
        return [
            'lang' => $this->lang->value,
            'script' => $this->script->value,
            'confidence' => $this->confidence,
            'reliable' => $this->isReliable(),
        ];
    }
}
