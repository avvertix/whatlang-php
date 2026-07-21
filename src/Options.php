<?php

declare(strict_types=1);

namespace Avvertix\WhatLang;

/**
 * Detection options.
 *
 * Ported from whatlang-rs (src/core/options.rs).
 */
final class Options
{
    public readonly FilterList $filterList;

    public function __construct(
        ?FilterList $filterList = null,
        public readonly Method $method = Method::Combined,
    ) {
        $this->filterList = $filterList ?? FilterList::all();
    }

    public function withFilterList(FilterList $filterList): self
    {
        return new self($filterList, $this->method);
    }

    public function withMethod(Method $method): self
    {
        return new self($this->filterList, $method);
    }
}
