<?php

namespace Georgeff\Pipeline\Test;

use Georgeff\Pipeline\Stage;
use PHPUnit\Framework\TestCase;
use Georgeff\Pipeline\PipelineFactory;
use Georgeff\Pipeline\PipelineInterface;

class PipelineFactoryTest extends TestCase
{
    public function test_factory_builder_returns_instance_of_pipeline_interface(): void
    {
        $pipline = PipelineFactory::build();

        $this->assertInstanceOf(PipelineInterface::class, $pipline);
    }

    public function test_build_with_stages(): void
    {
        $s1 = Stage::from(fn($payload) => $payload * 2);
        $s2 = Stage::from(fn($payload) => $payload - 1);

        $pipline = PipelineFactory::build($s1, $s2);

        $this->assertEquals(5, $pipline->process(3));
    }
}
