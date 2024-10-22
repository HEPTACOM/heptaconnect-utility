<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Utility\Collection\Scalar;

use Heptacom\HeptaConnect\Utility\Collection\AbstractCollection;

/**
 * @extends AbstractCollection<int>
 */
final class IntegerCollection extends AbstractCollection
{
    public function min(): ?int
    {
        if ($this->isEmpty()) {
            return null;
        }

        return (int) \min($this->items);
    }

    public function max(): ?int
    {
        if ($this->isEmpty()) {
            return null;
        }

        return (int) \max($this->items);
    }

    public function sum(): int
    {
        if ($this->isEmpty()) {
            return 0;
        }

        return (int) \array_sum($this->items);
    }

    #[\Override]
    public function asUnique(): static
    {
        $result = $this->withoutItems();

        $result->push(\array_keys(\array_flip($this->items)));

        return $result;
    }

    #[\Override]
    protected function isValidItem(mixed $item): bool
    {
        return \is_int($item);
    }
}
