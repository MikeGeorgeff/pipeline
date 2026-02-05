<?php

namespace Georgeff\Pipeline;

interface PipelineInterface
{
    /**
     * Add a stage to the pipeline
     */
    public function pipe(StageInterface $stage): PipelineInterface;

    /**
     * Process the payload through the pipeline
     *
     * @param mixed $payload
     *
     * @return mixed
     */
    public function process(mixed $payload): mixed;
}
