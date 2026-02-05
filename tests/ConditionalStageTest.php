<?php

namespace Georgeff\Pipeline\Test;

use Georgeff\Pipeline\Stage;
use Georgeff\Pipeline\Pipeline;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Georgeff\Pipeline\ConditionalStage;

class ConditionalStageTest extends TestCase
{
    public function test_executes_true_branch_when_condition_is_true(): void
    {
        $stage = new ConditionalStage(
            fn($payload) => true,
            Stage::from(fn($payload) => $payload * 2),
            Stage::from(fn($payload) => $payload * 3)
        );

        $result = $stage(5);

        $this->assertEquals(10, $result);
    }

    public function test_executes_false_branch_when_condition_is_false(): void
    {
        $stage = new ConditionalStage(
            fn($payload) => false,
            Stage::from(fn($payload) => $payload * 2),
            Stage::from(fn($payload) => $payload * 3)
        );

        $result = $stage(5);

        $this->assertEquals(15, $result);
    }

    public function test_returns_payload_when_condition_is_false_and_no_false_branch(): void
    {
        $stage = new ConditionalStage(
            fn($payload) => false,
            Stage::from(fn($payload) => $payload * 2)
        );

        $result = $stage(5);

        $this->assertEquals(5, $result);
    }

    public function test_condition_receives_payload(): void
    {
        $receivedPayload = null;

        $stage = new ConditionalStage(
            function ($payload) use (&$receivedPayload) {
                $receivedPayload = $payload;
                return true;
            },
            Stage::from(fn($payload) => $payload)
        );

        $stage(['key' => 'value']);

        $this->assertEquals(['key' => 'value'], $receivedPayload);
    }

    public function test_throws_exception_when_condition_returns_non_boolean(): void
    {
        $stage = new ConditionalStage(
            fn($payload) => $payload,
            Stage::from(fn($payload) => $payload)
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Condition must return a boolean');

        $stage('not a boolean');
    }

    public function test_throws_exception_when_condition_returns_integer(): void
    {
        $stage = new ConditionalStage(
            fn($payload) => 1,
            Stage::from(fn($payload) => $payload)
        );

        $this->expectException(InvalidArgumentException::class);

        $stage(5);
    }

    public function test_works_within_pipeline(): void
    {
        $pipeline = (new Pipeline())
            ->pipe(Stage::from(fn($payload) => $payload + 1))
            ->pipe(new ConditionalStage(
                fn($payload) => $payload > 5,
                Stage::from(fn($payload) => $payload * 10),
                Stage::from(fn($payload) => $payload * 2)
            ))
            ->pipe(Stage::from(fn($payload) => $payload + 1));

        $this->assertEquals(81, $pipeline->process(7)); // (7+1) * 10 + 1 = 81
        $this->assertEquals(9, $pipeline->process(3));  // (3+1) * 2 + 1 = 9
    }

    public function test_branches_can_be_pipelines(): void
    {
        $truePipeline = (new Pipeline())
            ->pipe(Stage::from(fn($payload) => $payload * 2))
            ->pipe(Stage::from(fn($payload) => $payload + 1));

        $falsePipeline = (new Pipeline())
            ->pipe(Stage::from(fn($payload) => $payload * 3))
            ->pipe(Stage::from(fn($payload) => $payload - 1));

        $stage = new ConditionalStage(
            fn($payload) => $payload > 10,
            $truePipeline,
            $falsePipeline
        );

        $this->assertEquals(25, $stage(12)); // 12 * 2 + 1 = 25
        $this->assertEquals(14, $stage(5));  // 5 * 3 - 1 = 14
    }
}
