<?php

namespace Georgeff\Pipeline\Test;

use SplQueue;
use Georgeff\Pipeline\Stage;
use Georgeff\Pipeline\Pipeline;
use PHPUnit\Framework\TestCase;
use Georgeff\Pipeline\StageInterface;
use Georgeff\Pipeline\StoppableInterface;

class PipelineTest extends TestCase
{
    public function test_process_pipeline_stages(): void
    {
        $pipeline = (new Pipeline())
            ->pipe(Stage::from(fn($payload) => $payload * 2))
            ->pipe(Stage::from(fn($payload) => $payload - 1));

        $result = $pipeline->process(3);

        $this->assertEquals(5, $result);
    }

    public function test_pipeline_processes_first_in_first_out(): void
    {
        $pipeline = (new Pipeline())
            ->pipe(Stage::from(fn($payload) => [...$payload, 'first']))
            ->pipe(Stage::from(fn($payload) => [...$payload, 'second']))
            ->pipe(Stage::from(fn($payload) => [...$payload, 'third']));

        $result = $pipeline->process([]);

        $this->assertEquals(['first', 'second', 'third'], $result);
    }

    public function test_pipeline_constructor_accepts_prebuilt_stage_queue(): void
    {
        $s1 = Stage::from(fn($payload) => $payload * 2);
        $s2 = Stage::from(fn($payload) => $payload - 1);

        $stages = new SplQueue();
        $stages->enqueue($s1);
        $stages->enqueue($s2);

        $pipeline = new Pipeline($stages);

        $result = $pipeline->process(3);

        $this->assertEquals(5, $result);
    }

    public function test_stoppable_stage_halts_the_pipeline_when_condition_met(): void
    {
        $stoppable = new class implements StageInterface, StoppableInterface {
            public function __invoke(mixed $payload): mixed
            {
                return $payload * 2;
            }

            public function shouldStop(mixed $payload): bool
            {
                return true;
            }
        };

        $pipeline = (new Pipeline())
            ->pipe(Stage::from(fn($payload) => $payload + 5))
            ->pipe($stoppable)
            ->pipe(Stage::from(fn($payload) => $payload + 100));

        $result = $pipeline->process(3);

        $this->assertEquals(16, $result);
    }

    public function test_pipeline_can_be_used_as_stage(): void
    {
        $p1 = (new Pipeline())
            ->pipe(Stage::from(fn($payload) => $payload * 2))
            ->pipe(Stage::from(fn($payload) => $payload - 1));

        $pipeline = (new Pipeline())
            ->pipe($p1)
            ->pipe(Stage::from(fn($payload) => $payload + 10));

        $result = $pipeline->process(3);

        $this->assertEquals(15, $result);
    }

    public function test_pipeline_is_immutable(): void
    {
        $base = (new Pipeline())
            ->pipe(Stage::from(fn($payload) => $payload + 1));

        $branch1 = $base->pipe(Stage::from(fn($payload) => $payload * 2));
        $branch2 = $base->pipe(Stage::from(fn($payload) => $payload * 3));

        $this->assertEquals(8, $branch1->process(3));  // (3 + 1) * 2 = 8
        $this->assertEquals(12, $branch2->process(3)); // (3 + 1) * 3 = 12
        $this->assertEquals(4, $base->process(3));     // 3 + 1 = 4 (unchanged)
    }
}
