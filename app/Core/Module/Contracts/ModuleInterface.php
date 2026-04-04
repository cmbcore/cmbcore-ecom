<?php

declare(strict_types=1);

namespace App\Core\Module\Contracts;

interface ModuleInterface
{
    public function getName(): string;

    public function getAlias(): string;

    public function getVersion(): string;

    public function isEnabled(): bool;

    /**
     * @return array<int, string>
     */
    public function getDependencies(): array;

    /**
     * @return array<int, string>
     */
    public function getProviders(): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getAdminMenu(): array;

    /**
     * @return array<string, string>
     */
    public function getAdminPages(): array;

    public function getPath(): string;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}