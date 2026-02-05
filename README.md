# Pipeline

An immutable pipeline library for PHP 8.2+.

## Installation

```bash
composer require georgeff/pipeline
```

## Basic Usage

```php
use Georgeff\Pipeline\Stage;
use Georgeff\Pipeline\Pipeline;

$pipeline = (new Pipeline())
    ->pipe(Stage::from(fn($payload) => $payload * 2))
    ->pipe(Stage::from(fn($payload) => $payload + 1));

$result = $pipeline->process(5); // 11
```

## Stages

All stages must implement `StageInterface`:

```php
use Georgeff\Pipeline\StageInterface;

final class MultiplyStage implements StageInterface
{
    public function __construct(private readonly int $factor)
    {
    }

    public function __invoke(mixed $payload): mixed
    {
        return $payload * $this->factor;
    }
}

$pipeline = (new Pipeline())
    ->pipe(new MultiplyStage(2))
    ->pipe(new MultiplyStage(3));

$result = $pipeline->process(5); // 30
```

For simple transformations, use the `Stage` helper:

```php
$pipeline = (new Pipeline())
    ->pipe(Stage::from(fn($payload) => $payload * 2));
```

## Immutability

The pipeline is immutable. Each call to `pipe()` returns a new instance:

```php
$base = (new Pipeline())
    ->pipe(new ValidateOrder());

$adminPipeline = $base->pipe(new AdminCheck());
$userPipeline = $base->pipe(new UserCheck());

// $base remains unchanged
```

## Factory

Build pipelines with initial stages:

```php
use Georgeff\Pipeline\PipelineFactory;

$pipeline = PipelineFactory::build(
    new ValidateOrder(),
    new ProcessPayment(),
    new SendConfirmation()
);
```

## Conditional Branching

Execute different stages based on a condition:

```php
use Georgeff\Pipeline\ConditionalStage;

$pipeline = (new Pipeline())
    ->pipe(new ConditionalStage(
        fn($order) => $order->isPriority(),
        new PriorityProcessing(),
        new StandardProcessing()
    ));
```

The false branch is optional. If omitted, the payload passes through unchanged:

```php
$pipeline = (new Pipeline())
    ->pipe(new ConditionalStage(
        fn($order) => $order->needsReview(),
        new FlagForReview()
    ));
```

## Switch Branching

Select a stage based on a key:

```php
use Georgeff\Pipeline\SwitchStage;

$pipeline = (new Pipeline())
    ->pipe(new SwitchStage(
        fn($order) => $order->getType(),
        [
            'digital' => new ProcessDigitalOrder(),
            'physical' => new ProcessPhysicalOrder(),
            'subscription' => new ProcessSubscription(),
        ],
        new ProcessUnknownOrder() // optional default
    ));
```

## Early Termination

Implement `StoppableInterface` to halt pipeline execution:

```php
use Georgeff\Pipeline\StageInterface;
use Georgeff\Pipeline\StoppableInterface;

final class ValidationStage implements StageInterface, StoppableInterface
{
    public function __invoke(mixed $payload): mixed
    {
        $payload->validate();
        return $payload;
    }

    public function shouldStop(mixed $payload): bool
    {
        return $payload->hasErrors();
    }
}
```

## Nested Pipelines

Pipelines implement `StageInterface`, so they can be nested:

```php
$validation = (new Pipeline())
    ->pipe(new ValidateFields())
    ->pipe(new ValidateBusinessRules());

$processing = (new Pipeline())
    ->pipe(new CalculateTotals())
    ->pipe(new ApplyDiscounts());

$pipeline = (new Pipeline())
    ->pipe($validation)
    ->pipe($processing)
    ->pipe(new SaveOrder());
```

## License

MIT
