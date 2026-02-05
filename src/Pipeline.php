<?php

namespace Georgeff\Pipeline;

use SplQueue;

class Pipeline implements PipelineInterface, StageInterface
{
    /**
     * @var SplQueue<StageInterface>
     */
    private SplQueue $stages;

    /**
     * @param SplQueue<StageInterface>|null $stages
     */
    public function __construct(?SplQueue $stages = null)
    {
        $this->stages = $stages ?: new SplQueue();
    }

    /**
     * @inheritdoc
     *
     * @see \Georgeff\Pipeline\PipelineInterface::pipe()
     */
    public function pipe(StageInterface $stage): PipelineInterface
    {
        $stages = clone $this->stages;
        $stages->enqueue($stage);

        return new self($stages);
    }

    /**
     * @inheritdoc
     *
     * @see \Georgeff\Pipeline\PipelineInterface::process()
     */
    public function process(mixed $payload): mixed
    {
        foreach ($this->stages as $stage) {
            $payload = $stage($payload);

            if ($stage instanceof StoppableInterface && $stage->shouldStop($payload)) {
                break;
            }
        }

        return $payload;
    }

    /**
     * @inheritdoc
     *
     * @see \Georgeff\Pipeline\StageInterface::__invoke()
     */
    public function __invoke(mixed $payload): mixed
    {
        return $this->process($payload);
    }
}
