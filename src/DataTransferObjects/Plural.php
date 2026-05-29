<?php

declare(strict_types=1);

namespace EugeneErg\ICUMessageFormatParser\DataTransferObjects;

use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Contracts\ICUTypeInterface;
use LogicException;

use function is_int;

final readonly class Plural extends AbstractSelect
{
    /**
     * @param Types[] $numbers Exact-match cases keyed by numeric string (e.g. '0', '1')
     * @param int $offset Offset subtracted from the value before plural category evaluation.
     *                    The '#' placeholder inside branches reflects (value − offset).
     *                    Exact-match cases (=N) compare against the original value.
     */
    public function __construct(
        string $value,
        public Types $other,
        public Types|null $zero = null,
        public Types|null $one = null,
        public Types|null $two = null,
        public Types|null $few = null,
        public Types|null $many = null,
        public array $numbers = [],
        public int $offset = 0,
    ) {
        parent::__construct($value);
    }

    public function __toString(): string
    {
        $options = [];

        foreach ($this->getOptions() as $key => $value) {
            $options[] = $key . ' {' . $value->replaceVariableName($this->value, '#') . '}';
        }

        $offset = $this->offset !== 0 ? ' offset:' . $this->offset : '';

        return '{' . $this->value . ', plural,' . $offset . ' ' . implode(' ', $options) . '}';
    }

    public static function getName(): string
    {
        return 'plural';
    }

    /**
     * @param array<ICUTypeInterface[]|int> $options
     */
    public static function create(string $value, array $options = []): self
    {
        /** @var array{other?: Types, zero?: Types, one?: Types, two?: Types, few?: Types, many?: Types, numbers?: array<string, Types>, offset?: int} $arguments */
        $arguments = [];
        $argumentNames = ['zero', 'one', 'two', 'few', 'many', 'other'];

        // offset passed directly as int (programmatic use)
        if (isset($options['offset']) && is_int($options['offset'])) {
            $arguments['offset'] = $options['offset'];
            unset($options['offset']);
        }

        foreach ($argumentNames as $argumentName) {
            if (isset($options[$argumentName])) {
                /** @var ICUTypeInterface[] $optionValue */
                $optionValue = $options[$argumentName];
                $arguments[$argumentName] = (new Types($optionValue))->replaceVariableName('#', $value);
                unset($options[$argumentName]);
            }
        }

        foreach ($options as $key => $option) {
            if (!preg_match('/\\A=\\d+\\z/', $key)) {
                throw new LogicException('Invalid option "' . $key . '"');
            }

            /** @var ICUTypeInterface[] $option */
            $arguments['numbers'][substr($key, 1)] = (new Types($option))->replaceVariableName('#', $value);
        }

        if (!isset($arguments['other'])) {
            $arguments['other'] = new Types();
        }

        return new self(
            value: $value,
            other: $arguments['other'],
            zero: $arguments['zero'] ?? null,
            one: $arguments['one'] ?? null,
            two: $arguments['two'] ?? null,
            few: $arguments['few'] ?? null,
            many: $arguments['many'] ?? null,
            numbers: $arguments['numbers'] ?? [],
            offset: $arguments['offset'] ?? 0,
        );
    }

    public function replaceRecursive(array $replace): self
    {
        return new self(
            value: $this->value,
            other: $this->other->replaceRecursive($replace),
            zero: $this->zero?->replaceRecursive($replace),
            one: $this->one?->replaceRecursive($replace),
            two: $this->two?->replaceRecursive($replace),
            few: $this->few?->replaceRecursive($replace),
            many: $this->many?->replaceRecursive($replace),
            numbers: array_map(static fn (Types $types) => $types->replaceRecursive($replace), $this->numbers),
            offset: $this->offset,
        );
    }

    protected function getOptions(): array
    {
        /** @var array<Types|null> $namedOptions */
        $namedOptions = [
            'zero' => $this->zero,
            'one' => $this->one,
            'two' => $this->two,
            'few' => $this->few,
            'many' => $this->many,
        ];
        $result = [];

        foreach ($namedOptions as $key => $value) {
            if ($value !== null) {
                $result[$key] = $value;
            }
        }

        foreach ($this->numbers as $key => $value) {
            $result['=' . $key] = $value;
        }

        $result['other'] = $this->other;

        return $result;
    }
}
