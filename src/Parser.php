<?php

declare(strict_types = 1);

namespace EugeneErg\ICUMessageFormatParser;

use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Date;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Duration;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\ICUTypeInterface;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Ordinal;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Pattern;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Plural;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Select;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\SelectOrdinal;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\SpellOut;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Text;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Time;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Types;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Variable;
use EugeneErg\StringParser\DataTransferObjects\Result;
use EugeneErg\StringParser\DataTransferObjects\Value;
use LogicException;

readonly class Parser
{
    public const TEXT = 'text';
    public const OBJECT = 'object';
    public const VARIABLE = 'variable';
    public const TYPE = 'type';
    public const OPTIONS = 'options';
    public const NESTED = 'nested';
    public const OPTION = 'option';
    public const SKELETON = 'skeleton';
    public const PATTERN = 'pattern';
    public const QUOTA = 'quota';

    /**
     * @param array<string, class-string<ICUTypeInterface>> $classes
     */
    public function __construct(
        private string $parserPath = __DIR__ . '/Template.php',
        private array $classes = [
            self::PATTERN => Pattern::class,
            self::VARIABLE => Variable::class,
            self::TEXT => Text::class,
            'select' => Select::class,
            'plural' => Plural::class,
            'selectordinal' => SelectOrdinal::class,
            'number' => Number::class,
            'date' => Date::class,
            'time' => Time::class,
            'spellout' => SpellOut::class,
            'ordinal' => Ordinal::class,
            'duration' => Duration::class,
        ],
    ) {
    }

    public function parse(string $formatMessage): Types
    {
        $structure = $this->getStructure($formatMessage);

        return new Types($this->parsePattern($structure->children));
    }

    private function getStructure(string $formatMessage): Result
    {
        return (new \EugeneErg\StringParser\Parser())->parse(require $this->parserPath, $formatMessage);
    }

    /**
     * @param array<Result|Value> $children
     */
    private function parsePattern(array $children): array
    {
        $result = [];
        $message = '';

        foreach ($children as $child) {
            if ($child->name === self::PATTERN || $child->name === self::QUOTA) {
                $message .= $child->value;
            } elseif ($child->name === self::TEXT) {
                if ($message !== '') {
                    $result[] = $this->createClass(self::PATTERN, $message);
                    $message = '';
                }

                $result[] = $this->createClass(self::TEXT, implode('', array_column($child->children, 'value')));
            } elseif ($child->name === self::OBJECT || $child->name === self::VARIABLE) {
                if ($message !== '') {
                    $result[] = $this->createClass(self::PATTERN, $message);
                    $message = '';
                }

                $result[] = $this->parseClass($child);
            }
        }

        if ($message !== '') {
            $result[] = $this->createClass(self::PATTERN, $message);
        }

        return $result;
    }

    private function parseClass(Result $class): ICUTypeInterface
    {
        return match (count($class->children)) {
            1 => $this->createClass(self::VARIABLE, $class->children[0]->value),
            2, 3 => $this->createClass($class->children[1]->value, $class->children[0]->value, $class->children[2] ?? null),
            default => throw new LogicException('Unexpected structure.'),
        };
    }

    private function createClass(string $type, string $value, ?Result $options = null): ICUTypeInterface
    {
        return $this->classes[$type]::create($value, $options === null ? [] : $this->parseOptions($options));
    }

    private function parseOptions(Result $options): array
    {
        return match ($options->name) {
            self::SKELETON => $this->getSkeletonOptions($options->children),
            self::NESTED => $this->getNestedOptions($options->children),
            self::PATTERN => $this->getTemplateOptions($options->children),
            default => throw new LogicException('Unexpected option type'),
        };
    }

    /**
     * @param Value[] $children
     */
    private function getSkeletonOptions(array $children): array
    {
        return array_merge(['::'], array_map(static fn (Value $value) => $value->value, $children));
    }

    /**
     * @param array<Result|Value> $children
     */
    private function getNestedOptions(array $children): array
    {
        $result = [];

        foreach ($children as $child) {
            $key = $child->children[0]->value;

            if (isset($result[$key])) {
                throw new LogicException('Duplicate option key');
            }

            $result[$key] = $this->parsePattern($child->children[1]->children);
        }

        return $result;
    }

    /**
     * @param array<Value|Result> $children
     */
    private function getTemplateOptions(array $children): array
    {
        $result = [];
        $message = '';

        foreach ($children as $child) {
            if ($child->name === self::TEXT) {
                if ($message !== '') {
                    $result[] = $this->createClass(self::PATTERN, $message);
                    $message = '';
                }

                $result[] = $this->createClass(self::TEXT, implode('', array_column($child->children, 'value')));
            } else {
                $message .= $child->value;
            }
        }

        if ($message !== '') {
            $result[] = $this->createClass(self::PATTERN, $message);
        }

        return $result;
    }
}