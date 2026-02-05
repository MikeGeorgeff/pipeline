<?php

namespace Georgeff\Pipeline;

use Closure;
use InvalidArgumentException;

final class SwitchStage implements StageInterface
{
    /**
     * @var \Closure
     */
    private Closure $selector;

    /**
     * @var \Georgeff\Pipeline\StageInterface[]
     */
    private array $branches;

    /**
     * @var \Georgeff\Pipeline\StageInterface|null
     */
    private ?StageInterface $default;

    /**
     * @param \Closure                           $selector
     * @param \Georgeff\Pipeline\StageInterface[]    $branches
     * @param \Georgeff\Pipeline\StageInterface|null $default
     */
    public function __construct(Closure $selector, array $branches, ?StageInterface $default = null)
    {
        $this->selector = $selector;
        $this->branches = $branches;
        $this->default  = $default;
    }

    public function __invoke(mixed $payload): mixed
    {
        $key = ($this->selector)($payload);

        if (!is_string($key) && !is_int($key)) {
            throw new InvalidArgumentException('Selector must return a string or integer');
        }

        if (isset($this->branches[$key])) {
            return ($this->branches[$key])($payload);
        }

        return $this->default ? ($this->default)($payload) : $payload;
    }
}
