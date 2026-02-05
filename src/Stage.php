<?php

namespace Georgeff\Pipeline;

use Closure;

final class Stage implements StageInterface
{
    private Closure $callback;

    public function __construct(Closure $callback)
    {
        $this->callback = $callback;
    }

    public static function from(Closure $callback): self
    {
        return new self($callback);
    }

    public function __invoke(mixed $payload): mixed
    {
        return ($this->callback)($payload);
    }
}
