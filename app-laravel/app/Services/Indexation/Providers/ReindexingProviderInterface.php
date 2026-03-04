<?php

namespace App\Services\Indexation\Providers;

use App\Services\Indexation\SubmitResult;

interface ReindexingProviderInterface
{
    /**
     * Soumet une liste d'URLs pour réindexation.
     *
     * @param  array<string> $urls
     */
    public function submitUrls(array $urls): SubmitResult;

    /**
     * Retourne le nom interne du provider (slug).
     */
    public function getName(): string;

    /**
     * Vérifie si le provider est configuré avec une clé API.
     */
    public function isAvailable(): bool;

    /**
     * Nombre maximum d'URLs acceptées par requête API.
     */
    public function getMaxBatchSize(): int;
}
