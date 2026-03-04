<?php

namespace App\Services\Indexation;

readonly class SubmitResult
{
    public function __construct(
        public string  $provider,
        public bool    $success,
        public int     $submitted,
        public array   $accepted_urls = [],
        public array   $rejected_urls = [],
        public ?string $task_id = null,
        public ?string $error = null,
        public array   $raw_response = [],
    ) {}

    public function hasError(): bool
    {
        return ! is_null($this->error);
    }
}
