<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Utility\Collection\Contract;

/**
 * Describes collection resembling classes.
 *
 * @template T
 *
 * @extends \ArrayAccess<int, T>
 * @extends \IteratorAggregate<int, T>
 */
interface CollectionInterface extends \IteratorAggregate, \Countable, \ArrayAccess, \JsonSerializable
{
    /**
     * Add new items to the collection.
     *
     * @param iterable<T> $items
     *
     * @throws \InvalidArgumentException
     */
    public function push(iterable $items): void;

    /**
     * Add new items to the collection but skip items, that do not meet the collection's validation criteria.
     *
     * @param iterable<T|mixed|null> $items
     */
    public function pushIgnoreInvalidItems(iterable $items): void;

    /**
     * Removes and returns the last element of the collection.
     * When the collection is empty, null is returned.
     *
     * @return T|null
     */
    public function pop();

    /**
     * Removes and returns the first element of the collection.
     * When the collection is empty, null is returned.
     *
     * @return T|null
     */
    public function shift();

    /**
     * Removes all entries of the collection.
     */
    public function clear(): void;

    /**
     * Returns true, when no entry is in the collection, otherwise false.
     */
    public function isEmpty(): bool;

    /**
     * Returns the first element of the collection.
     * When the collection is empty, null is returned.
     *
     * @return T|null
     */
    public function first();

    /**
     * Returns the last element of the collection.
     * When the collection is empty, null is returned.
     *
     * @return T|null
     */
    public function last();

    /**
     * Returns an iterable list of items, that are checked by the given callable.
     *
     * @param callable(mixed):bool $filterFn
     */
    public function filter(callable $filterFn): static;

    /**
     * Returns an iterable list of anything, that is returned for each item by the given callable.
     *
     * @template TMapResult
     *
     * @phpstan-param callable(T, array-key):TMapResult|callable(T):TMapResult $mapFn
     *
     * @return iterable<int, TMapResult>
     */
    public function map(callable $mapFn): iterable;

    /**
     * Returns an iterable list of values, that are pulled of each item by its property name, getter name or array index.
     */
    public function column(?string $valueAccessor, ?string $keyAccessor = null): iterable;

    /**
     * Create a new collection of the same type, but without any content.
     */
    public function withoutItems(): static;

    /**
     * Group items in maximum $size big chunks. The last chunk can be less than $size items.
     *
     * @phpstan-param positive-int $size
     *
     * @phpstan-return iterable<static&self<T>>
     */
    public function chunk(int $size): iterable;

    /**
     * Returns the items as a fixed size array. This is useful to use with methods that don't support iterables.
     *
     * @return array<T>
     */
    public function asArray(): array;

    /**
     * Reorders the collection into the opposite order it is now.
     */
    public function reverse(): void;

    /**
     * Returns true, when the item is in the collection, otherwise false.
     *
     * @param T $value
     */
    public function contains($value): bool;

    /**
     * Returns a copy of this collection only containing items a single time.
     */
    public function asUnique(): static;
}
