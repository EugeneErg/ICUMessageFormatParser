<?php

declare(strict_types=1);

namespace EugeneErg\ICUMessageFormatParser\DataTransferObjects;

use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Contracts\ICUTypeInterface;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Contracts\ICUTypeVariableInterface;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\Skeleton;

use function count;
use function is_string;

final readonly class Number implements ICUTypeInterface, ICUTypeVariableInterface
{
    public function __construct(public string $value, public Skeleton|Message $options, public bool $fromExplicitSkeleton = false)
    {
    }

    public function __toString(): string
    {
        $optionStr = (string) $this->options;

        // When Skeleton was created from explicit '::' syntax, ensure '::' prefix is preserved
        if ($this->fromExplicitSkeleton && $this->options instanceof Skeleton) {
            if (!str_starts_with($optionStr, '::')) {
                $optionStr = '::' . $optionStr;
            }
        }

        return '{' . $this->value . ', number' . ($optionStr === '' ? '' : ', ' . $optionStr) . '}';
    }

    public static function create(string $value, array $options = []): self
    {
        /** @var array<Pattern|string|Text> $options */
        $fromExplicitSkeleton = isset($options[0]) && $options[0] === '::';

        return new self($value, self::makeOptions($options), $fromExplicitSkeleton);
    }

    /**
     * @param array<Pattern|string|Text> $options
     */
    /**
     * @param array<Pattern|string|Text> $options
     */
    private static function makeOptions(array $options): Skeleton|Message
    {
        if ($options === []) {
            return new Skeleton();
        }

        if ($options[0] === '::') {
            unset($options[0]);

            /** @var array<int, string> $skeletonTokens */
            $skeletonTokens = array_values(array_filter($options, 'is_string'));

            return Skeleton::createFromOptions($skeletonTokens);
        }

        if (count($options) === 1 && $options[0] instanceof Pattern) {
            $skeleton = Skeleton::tryCreateFromPattern($options[0]);

            if ($skeleton !== null) {
                return $skeleton;
            }
        }

        $messageArgs = array_map(
            static fn (Pattern|string|Text $o): Pattern|Text => is_string($o) ? new Pattern($o) : $o,
            $options,
        );

        return new Message(...$messageArgs);
    }

    public function getAllVariants(array $cases = []): array
    {
        return [new Variant(types: new Types([$this]))];
    }

    public function getAllVariables(): array
    {
        return [$this->value];
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
