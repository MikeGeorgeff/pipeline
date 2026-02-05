<?php

namespace Georgeff\Pipeline;

interface StoppableInterface
{
    /**
     * Determine if the pipeline process should be stopped
     *
     * @param mixed $payload
     *
     * @return bool
     */
    public function shouldStop(mixed $payload): bool;
}
