<?php

namespace Georgeff\Pipeline;

use Closure;
use InvalidArgumentException;

final class ConditionalStage implements StageInterface
{
    private Closure $condition;
    private StageInterface $trueBranch;
    private ?StageInterface $falseBranch;

    /**
     * @param \Closure                           $condition
     * @param \Georgeff\Pipeline\StageInterface      $trueBranch
     * @param \Georgeff\Pipeline\StageInterface|null $falseBranch
     */
    public function __construct(Closure $condition, StageInterface $trueBranch, ?StageInterface $falseBranch = null)
    {
        $this->condition   = $condition;
        $this->trueBranch  = $trueBranch;
        $this->falseBranch = $falseBranch;
    }

    public function __invoke(mixed $payload): mixed
    {
        $condition = ($this->condition)($payload);

        if (!is_bool($condition)) {
            throw new InvalidArgumentException('Condition must return a boolean');
        }

        if ($condition) {
            return ($this->trueBranch)($payload);
        }

        return $this->falseBranch ? ($this->falseBranch)($payload) : $payload;
    }
}
