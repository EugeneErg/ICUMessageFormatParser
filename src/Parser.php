<?php

declare(strict_types=1);

namespace EugeneErg\ICUMessageFormatParser;

use EugeneErg\ICUMessageFormatParser\DataTransferObjects\AbstractSelect;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Cases;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Contracts\ICUTypeInterface;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Date;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Duration;
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
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Variant;
use EugeneErg\StringParser\DataTransferObjects\Result;
use EugeneErg\StringParser\DataTransferObjects\Root;
use EugeneErg\StringParser\DataTransferObjects\Value;
use LogicException;

use function array_key_exists;
use function assert;
use function count;
use function in_array;
use function is_string;

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
     * Types that support offset:N.
     */
    private const OFFSET_TYPES = ['plural', 'selectordinal'];

    /**
     * @param array<string, class-string<ICUTypeInterface>> $classes
     */
    public function __construct(
        private string $parserPath = __DIR__ . '/../Template.php',
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

    public function quote(string $text): string
    {
        return (string) preg_replace(['{\'}', '{[{}]+}'], ['\'\'', '\'$0\''], $text);
    }

    /**
     * @param (callable(Variant, string): string)|null $makeKey
     */
    public function typesToCases(Types $types, callable|null $makeKey = null): Cases
    {
        $variants = $types->getAllVariants();
        $cases = [];

        foreach ($variants as $intKey => $variant) {
            $key = $makeKey === null ? (string) $intKey : $makeKey($variant, (string) $intKey);
            $cases[$key] = $variant->cases;
        }

        return new Cases(
            array_column($variants, 'types'),
            new Types([$this->createFromCases($cases)]),
        );
    }

    public function casesToTypes(Cases $cases): Types
    {
        return $cases->variator->replaceRecursive($cases->types);
    }

    /**
     * @param ICUTypeInterface[][] $options
     */
    private function createFromName(string $name, string $value, array $options): ICUTypeInterface
    {
        return $this->classes[$name]::create($value, $options);
    }

    private function getStructure(string $formatMessage): Result
    {
        $root = require $this->parserPath;
        assert($root instanceof Root);

        return (new \EugeneErg\StringParser\Parser())->parse($root, $formatMessage);
    }

    /**
     * @param array<Result|Value> $children
     *
     * @return ICUTypeInterface[]
     */
    private function parsePattern(array $children): array
    {
        $result = [];
        $message = '';

        foreach ($children as $child) {
            if ($child->name === self::PATTERN || $child->name === self::QUOTA) {
                assert($child instanceof Value);
                $message .= $child->value;
            } elseif ($child->name === self::TEXT) {
                assert($child instanceof Result);

                if ($message !== '') {
                    $result[] = $this->createClass(self::PATTERN, $message);
                    $message = '';
                }

                /** @var list<Value> $textValues */
                $textValues = $child->children;
                $result[] = $this->createClass(self::TEXT, implode('', array_map(static fn (Value $v) => $v->value, $textValues)));
            } elseif ($child->name === self::OBJECT || $child->name === self::VARIABLE) {
                assert($child instanceof Result);

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
        $children = $class->children;
        $cnt = count($children);

        if ($cnt === 1) {
            assert($children[0] instanceof Value);

            return $this->createClass(self::VARIABLE, $children[0]->value);
        }

        if ($cnt === 2 || $cnt === 3) {
            assert($children[0] instanceof Value);
            assert($children[1] instanceof Value);
            $opts = $children[2] ?? null;
            assert($opts === null || $opts instanceof Result);

            return $this->createClass($children[1]->value, $children[0]->value, $opts);
        }

        throw new LogicException('Unexpected structure.');
    }

    private function createClass(string $type, string $value, Result|null $options = null): ICUTypeInterface
    {
        return $this->classes[$type]::create($value, $options === null ? [] : $this->parseOptions($options, $type));
    }

    /**
     * @return array<string, ICUTypeInterface[]|int>|ICUTypeInterface[]|string[]
     */
    private function parseOptions(Result $options, string $type = ''): array
    {
        return match ($options->name) {
            self::SKELETON => $this->getSkeletonOptions($options->children),
            self::NESTED => $this->getNestedOptions($options->children, $type),
            self::PATTERN => $this->getTemplateOptions($options->children),
            default => throw new LogicException('Unexpected option type'),
        };
    }

    /**
     * @param array<Result|Value> $children
     *
     * @return string[]
     */
    private function getSkeletonOptions(array $children): array
    {
        $values = [];

        foreach ($children as $child) {
            assert($child instanceof Value);
            $values[] = $child->value;
        }

        return array_merge(['::'], $values);
    }

    /**
     * Parse nested plural/select options.
     *
     * @param array<Result|Value> $children
     *
     * @return array<string, ICUTypeInterface[]|int>
     */
    private function getNestedOptions(array $children, string $type = ''): array
    {
        $result = [];
        $supportsOffset = in_array($type, self::OFFSET_TYPES, true);
        $firstKey = true;

        foreach ($children as $child) {
            assert($child instanceof Result);
            assert($child->children[0] instanceof Value);
            $key = $child->children[0]->value;

            // StringParser glues "offset:N" with the following key (e.g. "offset:2one")
            // because its exclude mechanism strips spaces instead of stopping.
            // We split them here on the first option of offset-supporting types.
            if ($supportsOffset && $firstKey && preg_match('/\\Aoffset:(\\d+)(.+)\\z/', $key, $m)) {
                $result['offset'] = (int) $m[1];
                $key = $m[2];
            } elseif ($supportsOffset && $firstKey && preg_match('/\\Aoffset:(\\d+)\\z/', $key, $m)) {
                $result['offset'] = (int) $m[1];
                $firstKey = false;

                continue;
            }

            $firstKey = false;

            if (isset($result[$key])) {
                throw new LogicException('Duplicate option key "' . $key . '"');
            }

            assert($child->children[1] instanceof Result);
            $result[$key] = $this->parsePattern($child->children[1]->children);
        }

        return $result;
    }

    /**
     * @param array<Result|Value> $children
     *
     * @return ICUTypeInterface[]
     */
    private function getTemplateOptions(array $children): array
    {
        $result = [];
        $message = '';

        foreach ($children as $child) {
            if ($child->name === self::TEXT) {
                assert($child instanceof Result);

                if ($message !== '') {
                    $result[] = $this->createClass(self::PATTERN, $message);
                    $message = '';
                }

                /** @var list<Value> $textChildren */
                $textChildren = $child->children;
                $result[] = $this->createClass(self::TEXT, implode('', array_map(static fn (Value $v) => $v->value, $textChildren)));
            } else {
                assert($child instanceof Value);
                $message .= $child->value;
            }
        }

        if ($message !== '') {
            $result[] = $this->createClass(self::PATTERN, $message);
        }

        return $result;
    }

    /**
     * @param array<string, array<string, array<string, string|string[]|null>>> $cases
     */
    private function createFromCases(array $cases): Pattern|AbstractSelect
    {
        foreach ($cases as $classes) {
            foreach ($classes as $class => $names) {
                foreach ($names as $name => $value) {
                    $allCases = [];

                    foreach ($cases as $key => $allClasses) {
                        if (isset($allClasses[$class]) && array_key_exists($name, $allClasses[$class])) {
                            $allCases[$key] = $allClasses[$class][$name];
                            unset($cases[$key][$class][$name]);
                        }
                    }

                    return $this->createSelect($class, $name, $allCases, $cases);
                }
            }
        }

        $key = array_key_first($cases);

        return new Pattern((string) $key);
    }

    /**
     * @param array<string, string|string[]|null> $classCases
     * @param array<string, array<string, array<string, string|string[]|null>>> $cases
     */
    private function createSelect(string $class, string $name, array $classCases, array $cases): AbstractSelect
    {
        $options = [];

        foreach ($classCases as $key => $value) {
            $option = is_string($value) ? $value : 'other';
            $options[$option][(string) $key] = $cases[$key];
        }

        foreach ($options as $option => $subCases) {
            $options[$option] = [$this->createFromCases($subCases)];
        }

        /** @var AbstractSelect */
        return $this->createFromName($class, $name, $options);
    }
}
