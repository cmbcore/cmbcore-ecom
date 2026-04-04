<?php

declare(strict_types=1);

namespace App\Core\Theme\Contracts;

interface ThemeInterface
{
    public function getName(): string;

    public function getAlias(): string;

    public function getVersion(): string;

    /**
     * @return array<int, string>
     */
    public function getSupports(): array;

    public function getPath(): string;
}