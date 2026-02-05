<?php

namespace Georgeff\Pipeline;

interface StageInterface
{
    /**
     * Process the payload
     *
     * @param mixed $payload
     *
     * @return mixed
     */
    public function __invoke(mixed $payload): mixed;
}
