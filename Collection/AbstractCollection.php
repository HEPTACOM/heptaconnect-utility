<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Utility\Collection;

use Heptacom\HeptaConnect\Utility\Collection\Contract\CollectionInterface;
use Heptacom\HeptaConnect\Utility\Json\JsonSerializeObjectVarsTrait;
use Heptacom\HeptaConnect\Utility\Php\SetStateTrait;

/**
 * @template T
 *
 * @template-implements CollectionInterface<T>
 */
abstract class AbstractCollection implements CollectionInterface
{
    use JsonSerializeObjectVarsTrait;
    use SetStateTrait;

    /**
     * @var array<int, T>
     */
    protected array $items = [];

    /**
     * @param iterable<T> $items
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(iterable $items = [])
    {
        $this->push($items);
    }

    public static function __set_state(array $an_array): static
    {
        $result = self::createStaticFromArray($an_array);
        /** @var array|mixed $items */
        $items = $an_array['items'] ?? [];

        if (\is_array($items) && $items !== []) {
            $result->items = $items;
        }

        return $result;
    }

    #[\Override]
    public function push(iterable $items): void
    {
        $newItems = [];

        foreach ($this->validateItems($items) as $item) {
            $newItems[] = $item;
        }

        if (\count($newItems) === 0) {
            return;
        }

        \array_push($this->items, ...$newItems);
    }

    #[\Override]
    public function pushIgnoreInvalidItems(iterable $items): void
    {
        $this->push($this->filterValid($items));
    }

    #[\Override]
    public function pop()
    {
        return \array_pop($this->items);
    }

    #[\Override]
    public function shift()
    {
        return \array_shift($this->items);
    }

    #[\Override]
    public function clear(): void
    {
        $this->items = [];
    }

    #[\Override]
    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    #[\Override]
    public function count(): int
    {
        return \count($this->items);
    }

    #[\Override]
    public function jsonSerialize(): array
    {
        return \array_values($this->items);
    }

    #[\ReturnTypeWillChange]
    #[\Override]
    public function getIterator()
    {
        yield from $this->items;
    }

    /**
     * @param string|int $offset
     */
    #[\Override]
    public function offsetExists($offset): bool
    {
        return \array_key_exists($offset, $this->items);
    }

    /**
     * @param array-key $offset
     *
     * @return T|null
     */
    #[\Override]
    public function offsetGet($offset): mixed
    {
        if (!\is_numeric($offset)) {
            throw new \InvalidArgumentException();
        }

        return $this->items[(int) $offset] ?? null;
    }

    /**
     * @phpstan-param array-key|null $offset
     * @phpstan-param T   $value
     */
    #[\Override]
    public function offsetSet($offset, $value): void
    {
        if (\is_numeric($offset) && $this->isValidItem($value)) {
            $this->items[(int) $offset] = $value;
        }

        if ($offset === null) {
            $this->push([$value]);
        }
    }

    /**
     * @param string|int $offset
     */
    #[\Override]
    public function offsetUnset($offset): void
    {
        unset($this->items[$offset]);
    }

    /**
     * @return T|null
     */
    #[\Override]
    public function first()
    {
        $end = \reset($this->items);

        return $end === false ? null : $end;
    }

    /**
     * @return T|null
     */
    #[\Override]
    public function last()
    {
        $end = \end($this->items);

        return $end === false ? null : $end;
    }

    #[\Override]
    public function filter(callable $filterFn): static
    {
        $result = $this->withoutItems();

        $result->items = \array_values(\array_filter($this->items, $filterFn));

        return $result;
    }

    #[\Override]
    public function map(callable $mapFn): iterable
    {
        yield from \array_map($mapFn, $this->items, \array_keys($this->items));
    }

    #[\Override]
    public function column(?string $valueAccessor, ?string $keyAccessor = null): iterable
    {
        foreach ($this as $key => $value) {
            yield $this->executeAccessor($value, $keyAccessor, $key) => $this->executeAccessor($value, $valueAccessor, $value);
        }
    }

    #[\Override]
    public function chunk(int $size): iterable
    {
        $size = \max($size, 1);
        $buffer = [];
        $chunkIndex = 0;

        foreach ($this as $item) {
            $buffer[$chunkIndex++] = $item;

            if (($chunkIndex % $size) === 0) {
                $result = $this->withoutItems();
                $result->items = \array_values($buffer);
                yield $result;
                $buffer = [];
            }
        }

        if ($buffer !== []) {
            $result = $this->withoutItems();
            $result->items = \array_values($buffer);
            yield $result;
        }
    }

    /**
     * @return array<T>
     */
    #[\Override]
    public function asArray(): array
    {
        return $this->items;
    }

    #[\Override]
    public function reverse(): void
    {
        $this->items = \array_reverse($this->items);
    }

    #[\Override]
    public function contains($value): bool
    {
        return \in_array($value, $this->items, true);
    }

    #[\Override]
    public function asUnique(): static
    {
        $result = $this->withoutItems();

        foreach ($this->items as $item) {
            if (!$result->contains($item)) {
                $result->push([$item]);
            }
        }

        return $result;
    }

    #[\Override]
    public function withoutItems(): static
    {
        $that = clone $this;

        $that->clear();

        return $that;
    }

    /**
     * @phpstan-assert-if-true T $item
     */
    abstract protected function isValidItem(mixed $item): bool;

    /**
     * @return iterable<int, T>
     */
    protected function filterValid(iterable $items): iterable
    {
        foreach ($items as $item) {
            if ($this->isValidItem($item)) {
                yield $item;
            }
        }
    }

    /**
     * @throws \InvalidArgumentException
     *
     * @return iterable<T>
     */
    protected function validateItems(iterable $items): iterable
    {
        foreach ($items as $item) {
            if (!$this->isValidItem($item)) {
                throw new \InvalidArgumentException();
            }

            yield $item;
        }
    }

    protected function executeAccessor(mixed $item, ?string $accessor, mixed $fallback): mixed
    {
        if (!\is_string($accessor)) {
            return $fallback;
        }

        if (\is_object($item)) {
            if (\method_exists($item, $accessor)) {
                return $item->$accessor();
            }

            if (\property_exists($item, $accessor)) {
                return $item->$accessor;
            }

            return $fallback;
        }

        if (\is_array($item)) {
            return $item[$accessor] ?? $fallback;
        }

        return $fallback;
    }

    /**
     * Alternative implementation for @see contains to check contains by more detailed object comparision.
     * This is useful, when the collection contains items that can be equal even if they are not identical.
     *
     * @param T $value
     * @param Closure(T $a,    T $b): bool $equalsCondition
     */
    final protected function containsByEqualsCheck(mixed $value, \Closure $equalsCondition): bool
    {
        if (!$this->isValidItem($value)) {
            return false;
        }

        foreach ($this->items as $item) {
            if ($equalsCondition($item, $value)) {
                return true;
            }
        }

        return false;
    }
}
