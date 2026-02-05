<?php

namespace Georgeff\Pipeline\Test;

use Georgeff\Pipeline\Stage;
use Georgeff\Pipeline\Pipeline;
use InvalidArgumentException;
use Georgeff\Pipeline\SwitchStage;
use PHPUnit\Framework\TestCase;

class SwitchStageTest extends TestCase
{
    public function test_executes_matching_branch(): void
    {
        $stage = new SwitchStage(
            fn($payload) => 'a',
            [
                'a' => Stage::from(fn($payload) => $payload * 2),
                'b' => Stage::from(fn($payload) => $payload * 3),
            ]
        );

        $result = $stage(5);

        $this->assertEquals(10, $result);
    }

    public function test_selects_branch_based_on_selector_result(): void
    {
        $stage = new SwitchStage(
            fn($payload) => $payload['type'],
            [
                'add' => Stage::from(fn($payload) => $payload['value'] + 10),
                'multiply' => Stage::from(fn($payload) => $payload['value'] * 10),
                'subtract' => Stage::from(fn($payload) => $payload['value'] - 10),
            ]
        );

        $this->assertEquals(15, $stage(['type' => 'add', 'value' => 5]));
        $this->assertEquals(50, $stage(['type' => 'multiply', 'value' => 5]));
        $this->assertEquals(-5, $stage(['type' => 'subtract', 'value' => 5]));
    }

    public function test_executes_default_branch_when_no_match(): void
    {
        $stage = new SwitchStage(
            fn($payload) => 'unknown',
            [
                'a' => Stage::from(fn($payload) => $payload * 2),
            ],
            Stage::from(fn($payload) => $payload * 100)
        );

        $result = $stage(5);

        $this->assertEquals(500, $result);
    }

    public function test_returns_payload_when_no_match_and_no_default(): void
    {
        $stage = new SwitchStage(
            fn($payload) => 'unknown',
            [
                'a' => Stage::from(fn($payload) => $payload * 2),
            ]
        );

        $result = $stage(5);

        $this->assertEquals(5, $result);
    }

    public function test_selector_receives_payload(): void
    {
        $receivedPayload = null;

        $stage = new SwitchStage(
            function ($payload) use (&$receivedPayload) {
                $receivedPayload = $payload;
                return 'a';
            },
            [
                'a' => Stage::from(fn($payload) => $payload),
            ]
        );

        $stage(['key' => 'value']);

        $this->assertEquals(['key' => 'value'], $receivedPayload);
    }

    public function test_works_with_integer_keys(): void
    {
        $stage = new SwitchStage(
            fn($payload) => $payload % 3,
            [
                0 => Stage::from(fn($payload) => 'divisible by 3'),
                1 => Stage::from(fn($payload) => 'remainder 1'),
                2 => Stage::from(fn($payload) => 'remainder 2'),
            ]
        );

        $this->assertEquals('divisible by 3', $stage(9));
        $this->assertEquals('remainder 1', $stage(7));
        $this->assertEquals('remainder 2', $stage(8));
    }

    public function test_works_within_pipeline(): void
    {
        $pipeline = (new Pipeline())
            ->pipe(Stage::from(fn($payload) => $payload + 1))
            ->pipe(new SwitchStage(
                fn($payload) => $payload > 10 ? 'large' : 'small',
                [
                    'large' => Stage::from(fn($payload) => $payload * 2),
                    'small' => Stage::from(fn($payload) => $payload * 3),
                ]
            ))
            ->pipe(Stage::from(fn($payload) => $payload + 1));

        $this->assertEquals(25, $pipeline->process(11)); // (11+1) * 2 + 1 = 25
        $this->assertEquals(16, $pipeline->process(4));  // (4+1) * 3 + 1 = 16
    }

    public function test_branches_can_be_pipelines(): void
    {
        $addPipeline = (new Pipeline())
            ->pipe(Stage::from(fn($payload) => $payload + 10))
            ->pipe(Stage::from(fn($payload) => $payload + 5));

        $multiplyPipeline = (new Pipeline())
            ->pipe(Stage::from(fn($payload) => $payload * 2))
            ->pipe(Stage::from(fn($payload) => $payload * 3));

        $stage = new SwitchStage(
            fn($payload) => $payload < 10 ? 'add' : 'multiply',
            [
                'add' => $addPipeline,
                'multiply' => $multiplyPipeline,
            ]
        );

        $this->assertEquals(20, $stage(5));  // 5 + 10 + 5 = 20
        $this->assertEquals(72, $stage(12)); // 12 * 2 * 3 = 72
    }

    public function test_empty_branches_with_default(): void
    {
        $stage = new SwitchStage(
            fn($payload) => $payload,
            [],
            Stage::from(fn($payload) => 'default executed')
        );

        $result = $stage('anything');

        $this->assertEquals('default executed', $result);
    }

    public function test_throws_exception_when_selector_returns_non_string_or_int(): void
    {
        $stage = new SwitchStage(
            fn($payload) => ['invalid' => 'key'],
            [
                'a' => Stage::from(fn($payload) => $payload),
            ]
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Selector must return a string or integer');

        $stage(5);
    }

    public function test_throws_exception_when_selector_returns_object(): void
    {
        $stage = new SwitchStage(
            fn($payload) => new \stdClass(),
            [
                'a' => Stage::from(fn($payload) => $payload),
            ]
        );

        $this->expectException(InvalidArgumentException::class);

        $stage(5);
    }
}
