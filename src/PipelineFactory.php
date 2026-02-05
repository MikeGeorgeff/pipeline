<?php

namespace Georgeff\Pipeline;

use SplQueue;

class PipelineFactory
{
    /**
     * Build a pipeline
     */
    public static function build(StageInterface ...$stages): PipelineInterface
    {
        /** @var SplQueue<StageInterface> $queue */
        $queue = new SplQueue();

        foreach ($stages as $stage) {
            $queue->enqueue($stage);
        }

        return new Pipeline($queue);
    }
}
