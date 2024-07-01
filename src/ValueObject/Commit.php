<?php

declare(strict_types=1);

namespace Rector\ReleaseNotesGenerator\ValueObject;

final readonly class Commit
{
    public function __construct(
        private string $hash,
        private string $message,
        private string $date
    ) {
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getDate(): string
    {
        return $this->date;
    }
}
