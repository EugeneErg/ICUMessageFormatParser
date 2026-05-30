# ICU MessageFormat Parser for PHP

A strongly-typed, zero-dependency PHP 8.2+ library for **parsing**, **serialising**, and **transforming** ICU MessageFormat strings.

[![PHP](https://img.shields.io/badge/PHP-8.2%2B-blue)](https://www.php.net)
[![License: MIT](https://img.shields.io/badge/License-MIT-green)](LICENSE)

---

## Table of Contents

- [Features](#features)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Core Concepts](#core-concepts)
  - [Types — the message container](#types--the-message-container)
  - [ICU element classes](#icu-element-classes)
  - [Variant — a flat message branch](#variant--a-flat-message-branch)
  - [Cases — flattened variant set](#cases--flattened-variant-set)
- [Parsing ICU strings](#parsing-icu-strings)
- [Building messages programmatically](#building-messages-programmatically)
- [Flattening branching messages](#flattening-branching-messages)
- [Rebuilding from cases](#rebuilding-from-cases)
- [Number formatting skeletons](#number-formatting-skeletons)
  - [Format](#format)
  - [Notation](#notation)
  - [Sign display](#sign-display)
  - [Precision](#precision)
  - [Grouping](#grouping)
  - [Integer width](#integer-width)
  - [Rounding mode](#rounding-mode)
  - [Scale](#scale)
  - [Unit width](#unit-width)
  - [Currency](#currency)
  - [Measure units](#measure-units)
  - [Numbering system](#numbering-system)
  - [Decimal separator](#decimal-separator)
  - [Multi-token skeletons](#multi-token-skeletons)
- [Date and Time formatting](#date-and-time-formatting)
- [Utility methods](#utility-methods)
- [Supported ICU element types](#supported-icu-element-types)
- [Error handling](#error-handling)
- [Comparison with alternatives](#comparison-with-alternatives)
- [Architecture overview](#architecture-overview)
- [Running tests](#running-tests)
- [License](#license)

---

## Features

- **Full ICU MessageFormat parsing** — `select`, `plural`, `selectordinal`, `number`, `date`, `time`, `spellout`, `ordinal`, `duration`
- **Complete ICU Number Skeleton support** — all tokens from the [ICU spec](https://unicode-org.github.io/icu/userguide/format_parse/numbers/skeletons.html), including concise forms (`E0`, `K`, `KK`, `+!`, `,_`, `000`, …)
- **Lossless round-trip** — parse → serialise returns the canonical ICU form
- **Structural flattening** — convert any branching message into a flat list of linear variants
- **Rebuild from variants** — reconstruct a `select`/`plural` tree from a flat variant set
- **Strongly typed** — every ICU construct has its own readonly class or enum; no stringly-typed magic
- **Zero PHP extensions required** — standard `mbstring` / `pcre` only
- **PHP 8.2+ features** — `readonly` classes, enums, named arguments, intersection types throughout

---

## Installation

```bash
composer require eugene-erg/icu-message-format-parser
```

---

## Quick Start

```php
use EugeneErg\ICUMessageFormatParser\Parser;

$parser = new Parser();

// 1. Parse an ICU string into a typed object tree
$types = $parser->parse(
    '{gender, select, male {He} female {She} other {They}} liked {count, plural, one {1 post} other {# posts}}.'
);

// 2. Flatten all branching into a linear list of variants
$cases = $parser->typesToCases($types);
// → 3 gender × 2 plural = 6 flat variants

// 3. Inspect every variant
foreach ($cases->types as $variant) {
    echo (string) $variant, PHP_EOL;
}
// He liked 1 post.
// He liked {count} posts.
// She liked 1 post.
// She liked {count} posts.
// They liked 1 post.
// They liked {count} posts.

// 4. Rebuild from flat cases back into a structured Types tree
$rebuilt = $parser->casesToTypes($cases);
echo (string) $rebuilt; // canonical ICU string
```

---

## Core Concepts

### Types — the message container

`Types` is an ordered, immutable sequence of `ICUTypeInterface` elements. It is the central value object of the library.

```php
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Types;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Pattern;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Variable;

$types = new Types([
    new Pattern('Hello '),
    new Variable('name'),
    new Pattern('!'),
]);

echo (string) $types; // Hello {name}!
```

**Key `Types` methods:**

| Method | Description |
|--------|-------------|
| `getAllVariants(array $cases = [])` | Expand branching into `Variant[]` |
| `getAllVariables(): string[]` | Collect all substitution variable names |
| `setValues(array $values): self` | Replace named variables with `Text` literals |
| `replaceVariableName(string $from, string $to): self` | Rename a variable throughout |
| `replaceRecursive(array $replace): self` | Substitute `Pattern` placeholders with `Types` fragments |
| `quote(): self` | Wrap non-`Pattern` elements as `Text` (adds ICU escaping) |
| `map(callable): self` | Transform each element |
| `filter(callable): self` | Keep elements matching predicate |
| `getVariables(): self` | Return only `ICUTypeVariableInterface` elements |

### ICU element classes

Every element in a `Types` sequence implements `ICUTypeInterface`:

```
ICUTypeInterface (Stringable)
├── Pattern          — raw unescaped text fragment
├── Text             — ICU-quoted literal (single-quoted in the format string)
├── Variable         — simple substitution {name} or # (inside plural)
├── AbstractSelect   — branching constructs
│   ├── Select
│   ├── Plural
│   └── SelectOrdinal
├── Number           — {var, number, skeleton}
├── Date             — {var, date, format}
├── Time             — {var, time, format}
├── SpellOut         — {var, spellout}
├── Ordinal          — {var, ordinal}
└── Duration         — {var, duration}
```

All element classes are `final readonly` — once constructed they are immutable.

### Variant — a flat message branch

`Variant` is one linear path through a branching message:

```php
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Variant;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Types;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Pattern;

$variant = new Variant(
    types: new Types([new Pattern('He liked this.')]),
    cases: ['select' => ['gender' => 'male']],
);

echo (string) $variant->types; // He liked this.
print_r($variant->cases);      // ['select' => ['gender' => 'male']]
```

`cases` is a map of `SelectTypeName → variableName → branchLabel` that identifies which branch each `Select`/`Plural`/`SelectOrdinal` took to reach this variant. `null` means the `other` branch.

### Cases — flattened variant set

`Cases` bundles the flat `Types[]` list together with a `variator` — a `Types` tree of `Pattern` placeholders that records how to reconstruct the original branching structure.

```php
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Cases;

// $cases->types    — array of Types, one per flat variant
// $cases->variator — Types tree with Pattern placeholders
```

---

## Parsing ICU strings

```php
use EugeneErg\ICUMessageFormatParser\Parser;

$parser = new Parser();

// Simple variable
$types = $parser->parse('Hello {name}!');

// Plural
$types = $parser->parse('{count, plural, one {# item} other {# items}}');

// Select
$types = $parser->parse('{gender, select, male {He} female {She} other {They}}');

// SelectOrdinal
$types = $parser->parse('{place, selectordinal, one {#st} two {#nd} few {#rd} other {#th}}');

// Number with skeleton
$types = $parser->parse('{price, number, ::currency/EUR .00}');

// Date with format
$types = $parser->parse('{ts, date, long}');

// Date with skeleton
$types = $parser->parse('{ts, date, ::yMMMd}');

// Nested
$types = $parser->parse(
    '{gender, select, ' .
    '  male   {{count, plural, one {He has # item} other {He has # items}}} ' .
    '  other  {{count, plural, one {They have # item} other {They have # items}}}' .
    '}'
);
```

The parser is extensible — you can register custom type classes:

```php
$parser = new Parser(classes: [
    ...Parser::DEFAULT_CLASSES,
    'mytype' => MyCustomType::class,
]);
```

---

## Building messages programmatically

All element classes have a static `create()` factory and can also be constructed directly:

```php
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\{
    Types, Pattern, Variable, Text,
    Select, Plural, SelectOrdinal,
    Number, Date, Time, SpellOut, Duration, Ordinal,
    DateTimeFormat,
};
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\{
    Skeleton, Currency, Format, PrecisionFraction, Sign, Grouping,
};

// ── Literal text ──────────────────────────────────────────────────────────

$pat  = new Pattern('Hello ');           // unescaped; {, } will break ICU
$text = new Text("it's fine");          // auto-escaped to ICU 'it''s fine'
$var  = new Variable('name');            // {name}
$hash = new Variable('#');               // # (plural offset placeholder)

// ── Select ────────────────────────────────────────────────────────────────

$select = Select::create('gender', [
    'male'   => [new Pattern('He')],
    'female' => [new Pattern('She')],
    'other'  => [new Pattern('They')],
]);
// → {gender, select, male {He} female {She} other {They}}

// ── Plural ────────────────────────────────────────────────────────────────

$plural = Plural::create('count', [
    'one'   => [new Pattern('1 item')],
    'other' => [new Variable('#'), new Pattern(' items')], // # → {count}
]);
// → {count, plural, one {1 item} other {# items}}

// Exact-match cases
$plural2 = Plural::create('n', [
    '=0'    => [new Pattern('none')],
    '=1'    => [new Pattern('one')],
    'other' => [new Pattern('many')],
]);

// ── SelectOrdinal ─────────────────────────────────────────────────────────

$ordinal = SelectOrdinal::create('place', [
    'one'   => [new Pattern('1st')],
    'two'   => [new Pattern('2nd')],
    'few'   => [new Pattern('3rd')],
    'other' => [new Pattern('#th')],
]);

// ── Number ────────────────────────────────────────────────────────────────

// Default (no formatting options)
$num = Number::create('amount');
// → {amount, number}

// With skeleton tokens
$num = Number::create('price', ['::', 'currency/EUR', '.00']);
// → {price, number, ::currency/EUR .00}

// With strongly-typed Skeleton object
$num = new Number('price', new Skeleton(
    format:    new Currency('EUR'),
    sign:      Sign::Always,
    precision: new PrecisionFraction(2, 2),
    grouping:  Grouping::Min2,
));

// ── Date / Time ───────────────────────────────────────────────────────────

$date = new Date('created', DateTimeFormat::Long);
// → {created, date, long}

$date = new Date('created', 'yMMMd');          // skeleton
// → {created, date, ::yMMMd}

$time = new Time('ts', DateTimeFormat::Short);
// → {ts, time, short}

// ── Simple formatting types ───────────────────────────────────────────────

$spellout = new SpellOut('amount');    // {amount, spellout}
$duration  = new Duration('elapsed'); // {elapsed, duration}
$ordType   = new Ordinal('rank');     // {rank, ordinal}

// ── Composing a full message ──────────────────────────────────────────────

$message = new Types([
    new Variable('name'),
    new Pattern(' has '),
    Plural::create('count', [
        'one'   => [new Pattern('1 new message')],
        'other' => [new Variable('#'), new Pattern(' new messages')],
    ]),
    new Pattern(' as of '),
    new Date('date', DateTimeFormat::Medium),
]);

echo (string) $message;
// {name} has {count, plural, one {1 new message} other {# new messages}} as of {date, date}
```

---

## Flattening branching messages

`typesToCases()` expands every `select`/`plural`/`selectordinal` branch into a flat list of linear message variants. Use this for:

- Generating all possible message translations
- Static analysis / QA of ICU messages
- Feeding individual strings into external translation tools
- Rendering every path for screenshot/visual testing

```php
use EugeneErg\ICUMessageFormatParser\Parser;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Variant;

$parser = new Parser();
$types  = $parser->parse(
    '{gender, select, male {He} female {She} other {They}} '
  . '{count, plural, one {liked 1 post} other {liked # posts}}.'
);

$cases = $parser->typesToCases($types);

foreach ($cases->types as $i => $variant) {
    echo "[$i] " . (string) $variant, PHP_EOL;
}
// [0] He liked 1 post.
// [1] He liked {count} posts.
// [2] She liked 1 post.
// [3] She liked {count} posts.
// [4] They liked 1 post.
// [5] They liked {count} posts.
```

You can supply a custom key-maker to give variants meaningful names:

```php
$cases = $parser->typesToCases($types, function (Variant $variant, string $defaultKey): string {
    $parts = [];
    foreach ($variant->cases as $type => $vars) {
        foreach ($vars as $varName => $branch) {
            $parts[] = "$varName=$branch";
        }
    }
    return implode('|', $parts) ?: $defaultKey;
});
// Keys: "gender=male|plural=one", "gender=male|plural=other", …
```

### Accessing variant metadata

Each `Variant` carries the `cases` array showing which branch it took:

```php
foreach ($cases->types as $variant) {
    // $variant is a Types object, $cases->types is Types[]

    // To get the branch labels you need to call getAllVariants on the original Types
}

$variants = $types->getAllVariants();
foreach ($variants as $variant) {
    echo (string) $variant->types, PHP_EOL;
    // $variant->cases = ['select' => ['gender' => 'male'], 'plural' => ['count' => 'one']]
    print_r($variant->cases);
}
```

---

## Rebuilding from cases

`casesToTypes()` reconstructs the structured `Types` tree from a `Cases` object:

```php
$cases   = $parser->typesToCases($types);
$rebuilt = $parser->casesToTypes($cases);

echo (string) $rebuilt; // canonical ICU string (equivalent to original)
```

You can also modify the flat variants before rebuilding, enabling programmatic message editing:

```php
$cases = $parser->typesToCases($types);

// Replace variant [0] with a different text
$cases->types[0] = new Types([new Pattern('Il a aimé 1 publication.')]);

$rebuilt = $parser->casesToTypes($cases);
```

---

## Number formatting skeletons

`Skeleton` is a strongly-typed representation of an [ICU Number Skeleton](https://unicode-org.github.io/icu/userguide/format_parse/numbers/skeletons.html). All fields are optional and default to the ICU default value. The `__toString()` output is the canonical minimal skeleton string — no redundant tokens are emitted.

```php
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\Skeleton;

$sk = new Skeleton(); // all defaults
echo (string) $sk;   // "" — empty skeleton = default decimal format
```

### Format

```php
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\Format;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\Currency;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\MeasureUnit;

new Skeleton(Format::Decimal);      // default, emits nothing
new Skeleton(Format::Integer);      // "integer"
new Skeleton(Format::Percent);      // "percent"
new Skeleton(Format::Permille);     // "permille"
new Skeleton(Format::BaseUnit);     // "base-unit"
new Skeleton(new Currency());       // "currency"  (USD default)
new Skeleton(new Currency('EUR'));   // "currency/EUR"
new Skeleton(new MeasureUnit('length-meter')); // "::measure-unit/length-meter"
new Skeleton(new MeasureUnit('speed-kilometer-per-hour', 'duration-hour'));
// "::measure-unit/speed-kilometer-per-hour per-measure-unit/duration-hour"
```

### Notation

```php
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\{
    Notation, ScientificNotation, EngineeringNotation, ScientificOptions,
};

new Skeleton(notation: Notation::Standard);       // default, emits nothing
new Skeleton(notation: Notation::NotationSimple); // "::notation-simple"
new Skeleton(notation: Notation::CompactShort);   // "::compact-short"
new Skeleton(notation: Notation::CompactLong);    // "::compact-long"
new Skeleton(notation: new ScientificNotation()); // "::scientific"
new Skeleton(notation: new EngineeringNotation(new ScientificOptions())); // "::engineering"

// Scientific with options
new Skeleton(notation: new ScientificNotation(
    new ScientificOptions(
        exponentSign:      Sign::Always,  // → /sign-always
        minExponentDigits: 2,             // → /*ee
    )
));
// "::scientific/sign-always/*ee"

// Concise forms parsed (both read and write):
//   E0   → ScientificNotation
//   E00  → ScientificNotation(minExponentDigits=2)
//   EE0  → EngineeringNotation
//   K    → CompactShortNotation
//   KK   → CompactLongNotation
```

### Sign display

```php
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\Sign;

new Skeleton(sign: Sign::Auto);               // default, emits nothing
new Skeleton(sign: Sign::Always);             // "::sign-always"  (+! concise)
new Skeleton(sign: Sign::Never);              // "::sign-never"   (+_ concise)
new Skeleton(sign: Sign::Accounting);         // "::sign-accounting"
new Skeleton(sign: Sign::AccountingAlways);   // "::sign-accounting-always"
new Skeleton(sign: Sign::ExceptZero);         // "::sign-except-zero" (+? concise)
new Skeleton(sign: Sign::AccountingExceptZero); // "::sign-accounting-except-zero"
new Skeleton(sign: Sign::Negative);           // "::sign-negative"
new Skeleton(sign: Sign::AccountingNegative); // "::sign-accounting-negative"
```

### Precision

```php
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\{
    Precision, PrecisionFraction, PrecisionSignificant, PrecisionIncrement,
};

// Named precision
Precision::Integer;          // "precision-integer"
Precision::Unlimited;        // "precision-unlimited"
Precision::CurrencyStandard; // requires Currency format
Precision::CurrencyCash;     // requires Currency format

// Fraction precision
new PrecisionFraction(minFraction: 2, maxFraction: 2);        // ".00"
new PrecisionFraction(0, 2);                                   // ".##" (default)
new PrecisionFraction(2, null);                                // ".00*" (unlimited)
new PrecisionFraction(2, 2, trailingZeroHideIfWhole: true);   // ".00/w"

// Combined fraction + significant
new PrecisionFraction(
    minFraction:         0,
    maxFraction:         2,
    minSignificantDigits: 3,
    maxSignificantDigits: null,  // unlimited
);
// ".##/@@@*"

// Significant digits
new PrecisionSignificant(minDigits: 3, maxDigits: 3);          // "@@@"
new PrecisionSignificant(minDigits: 1, maxDigits: 3);          // "@##"
new PrecisionSignificant(minDigits: 3, maxDigits: null);       // "@@@*"
new PrecisionSignificant(3, 3, trailingZeroHideIfWhole: true); // "@@@/w"

// Increment precision
new PrecisionIncrement(0.05);  // "precision-increment/0.05"
new PrecisionIncrement(0.5);   // "precision-increment/0.5"
new PrecisionIncrement(50);    // "precision-increment/50"
```

### Grouping

```php
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\Grouping;

Grouping::Auto;       // default
Grouping::Off;        // "group-off"   (,_ concise)
Grouping::Min2;       // "group-min2"  (,? concise)
Grouping::OnAligned;  // "group-on-aligned" (,! concise)
Grouping::Thousands;  // "group-thousands"
```

### Integer width

```php
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\IntegerWidth;

new Skeleton(integerWidth: IntegerWidth::fromConcise(3));
// Serialises as "000" (concise form) → at least 3 integer digits

new Skeleton(integerWidth: new IntegerWidth(zeroFillTo: 1, truncateAt: 3));
// "::integer-width/##0"

IntegerWidth::trunc();
// "integer-width-trunc" — truncate all integer digits

new Skeleton(integerWidth: new IntegerWidth(zeroFillTo: 0, truncateAt: null));
// "::integer-width/*"
```

### Rounding mode

```php
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\RoundingMode;

new Skeleton(roundingMode: RoundingMode::HalfUp);
// "::rounding-mode-half-up"

// All modes:
// Ceiling, Floor, Down, Up, HalfEven, HalfDown, HalfUp, Unnecessary
```

### Scale

```php
new Skeleton(scale: 100.0);  // "::scale/100"
new Skeleton(scale: 0.01);   // "::scale/0.01"

// Special case: Percent + scale/100 → concise %x100
new Skeleton(format: Format::Percent, scale: 100.0); // "%x100"
```

### Unit width

```php
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\UnitWidth;

UnitWidth::Short;    // default
UnitWidth::Narrow;   // "::unit-width-narrow"
UnitWidth::FullName; // "::unit-width-full-name" (Currency or MeasureUnit)
UnitWidth::IsoCode;  // "::unit-width-iso-code"  (Currency only)
UnitWidth::Hidden;   // "::unit-width-hidden"     (Currency only)
```

### Currency

```php
new Skeleton(new Currency('EUR'));
// Minimal: "currency/EUR"

new Skeleton(new Currency('USD'));
// Minimal: "currency"  (USD is the special-cased default)

new Skeleton(
    format:    new Currency('JPY'),
    unitWidth: UnitWidth::FullName,
    precision: Precision::CurrencyCash,
);
// "::currency/JPY unit-width-full-name precision-currency-cash"
```

### Measure units

```php
new Skeleton(new MeasureUnit('length-meter'));
// "::measure-unit/length-meter"

new Skeleton(new MeasureUnit('speed-meter-per-second'));
// "::measure-unit/speed-meter-per-second"

new Skeleton(
    format:    new MeasureUnit('length-meter', perUnit: 'duration-second'),
    unitWidth: UnitWidth::FullName,
    precision: new PrecisionFraction(1, 2),
);
// "::measure-unit/length-meter per-measure-unit/duration-second unit-width-full-name .0#"
```

### Numbering system

```php
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\NumberingSystem;

new Skeleton(numberingSystem: new NumberingSystem('latin'));  // "::latin"
new Skeleton(numberingSystem: new NumberingSystem('arab'));   // "::numbering-system/arab"
new Skeleton(numberingSystem: new NumberingSystem('deva'));   // "::numbering-system/deva"
```

### Decimal separator

```php
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\DecimalSeparator;

DecimalSeparator::Auto;   // default, emits nothing
DecimalSeparator::Always; // "::decimal-always"
```

### Multi-token skeletons

Tokens are space-separated. The serialiser always emits the minimal canonical form:

```php
$sk = Skeleton::createFromOptions(['currency/EUR', 'sign-always', 'group-min2', '.00']);
echo (string) $sk;
// "::currency/EUR group-min2 sign-always .00"

// Round-trip: parse a skeleton string
$sk = Skeleton::createFromOptions(
    preg_split('/\s+/', 'compact-short sign-always @@@')
);
echo (string) $sk;
// "::compact-short sign-always @@@"
```

---

## Date and Time formatting

```php
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\{Date, Time, DateTimeFormat};

// Named formats
new Date('ts', DateTimeFormat::Short);   // {ts, date, short}
new Date('ts', DateTimeFormat::Medium);  // {ts, date}  (Medium is default, omitted)
new Date('ts', DateTimeFormat::Long);    // {ts, date, long}
new Date('ts', DateTimeFormat::Full);    // {ts, date, full}

// Skeleton strings (passed after "::")
new Date('ts', 'yMMMd');   // {ts, date, ::yMMMd}
new Date('ts', 'yMMMMd');  // {ts, date, ::yMMMMd}

// Same API for Time
new Time('ts', DateTimeFormat::Short);   // {ts, time, short}
new Time('ts', 'HHmmss');               // {ts, time, ::HHmmss}
```

---

## Utility methods

### Quoting text

`Parser::quote()` escapes a raw string for safe inclusion in an ICU pattern:

```php
$parser = new Parser();

echo $parser->quote("This {must} be 'escaped'");
// This '{must}' be ''escaped''
```

### Getting all variable names

```php
$vars = $types->getAllVariables();
// ['name', 'count', 'gender', …]
```

### Replacing variable names

```php
// Replace '#' with 'count' throughout (what Plural::create does internally)
$types = $types->replaceVariableName('#', 'count');
```

### Substituting values

```php
// Substitute variables with literal values (produces ICU-quoted Text nodes)
$result = $types->setValues(['name' => 'Alice', 'city' => 'Paris']);
// Variables become Text nodes; serialised with ICU single-quote escaping
```

### replaceRecursive

Replace `Pattern` placeholder nodes with arbitrary `Types` fragments. This is how `casesToTypes()` works internally:

```php
$variator   = new Types([new Pattern('0'), new Pattern(' and '), new Pattern('1')]);
$flatTypes  = [
    new Types([new Pattern('one')]),
    new Types([new Pattern('two')]),
];
$result = $variator->replaceRecursive($flatTypes);
echo (string) $result; // "one and two"
```

---

## Supported ICU element types

| ICU syntax | Class | Notes |
|---|---|---|
| `{var}` | `Variable` | Simple substitution |
| `{var, select, …}` | `Select` | String-keyed branching |
| `{var, plural, …}` | `Plural` | CLDR plural categories + `=N` exact match |
| `{var, selectordinal, …}` | `SelectOrdinal` | Ordinal plural categories |
| `{var, number}` | `Number` | Default decimal format |
| `{var, number, ::skeleton}` | `Number` + `Skeleton` | Full ICU skeleton |
| `{var, number, pattern}` | `Number` + `Message` | Legacy decimal pattern |
| `{var, date}` | `Date` | Medium date (default) |
| `{var, date, short\|medium\|long\|full}` | `Date` + `DateTimeFormat` | Named date format |
| `{var, date, ::skeleton}` | `Date` | Date skeleton |
| `{var, time, …}` | `Time` | Same options as Date |
| `{var, spellout}` | `SpellOut` | Spell-out number |
| `{var, ordinal}` | `Ordinal` | Ordinal number |
| `{var, duration}` | `Duration` | Duration formatting |
| `'quoted text'` | `Text` | ICU single-quoted literal |
| Raw text | `Pattern` | Unquoted text fragment |

### Plural case keywords

| Keyword | Meaning |
|---|---|
| `zero` | CLDR zero category |
| `one` | CLDR one (singular) category |
| `two` | CLDR two category |
| `few` | CLDR few category |
| `many` | CLDR many category |
| `other` | Fallback (always required) |
| `=N` | Exact numeric match |

---

## Error handling

```php
use LogicException;
use InvalidArgumentException;

// Invalid plural key
try {
    Plural::create('n', ['invalid' => [new Pattern('x')], 'other' => [new Pattern('y')]]);
} catch (LogicException $e) {
    // "Invalid option "invalid""
}

// Unknown skeleton token
try {
    Skeleton::createFromOptions(['totally-unknown-xyz']);
} catch (LogicException $e) {
    // "Unknown skeleton token: "totally-unknown-xyz""
}

// Currency precision on non-currency format
try {
    new Skeleton(format: Format::Decimal, precision: Precision::CurrencyStandard);
} catch (InvalidArgumentException $e) {
    // "Skeleton: precision-currency-* is only valid with a Currency format."
}

// Invalid IntegerWidth
try {
    new IntegerWidth(zeroFillTo: 5, truncateAt: 2); // truncate < fill
} catch (InvalidArgumentException $e) { /* … */ }

// Duplicate select key
try {
    $parser->parse('{x, select, a {1} a {2} other {3}}');
} catch (LogicException $e) {
    // "Duplicate option key"
}
```

---

## Comparison with alternatives

| Feature | **this library** | php-icu-message-formatter | MessageFormatter (intl) |
|---|:---:|:---:|:---:|
| Parse → object tree | ✅ | ❌ | ❌ |
| Serialise back to ICU | ✅ | ❌ | ❌ |
| Flatten to variants | ✅ | ❌ | ❌ |
| Rebuild from variants | ✅ | ❌ | ❌ |
| Strongly-typed skeleton | ✅ | ❌ | ❌ |
| Round-trip lossless | ✅ | n/a | n/a |
| Format at runtime | ❌* | ✅ | ✅ |
| PHP extension required | ❌ | ❌ | ✅ (`intl`) |
| PHP 8.2+ types/enums | ✅ | ❌ | n/a |
| Static analysis friendly | ✅ | partial | ❌ |

*This library is a **parser and transformer**, not a formatter. Pair it with `MessageFormatter` (intl) or `php-icu-message-formatter` to actually format messages at runtime.

### Recommended pairing

```php
// 1. Parse & analyse/transform with this library
$parser  = new Parser();
$types   = $parser->parse($icuString);
$cases   = $parser->typesToCases($types); // flatten for translation tooling

// 2. Format at runtime with intl
$fmt = new MessageFormatter('en_US', $icuString);
echo $fmt->format(['count' => 3, 'name' => 'Alice']);
```

---

## Architecture overview

```
ICU string
   │
   ▼ Parser::parse()
Types (tree: ICUTypeInterface[])
   │
   ├──── (string) cast ──────────────► canonical ICU string
   │
   ├──── getAllVariants() ───────────► Variant[]
   │                                     ├── types: Types   (flat message)
   │                                     └── cases: array   (branch labels)
   │
   ▼ Parser::typesToCases()
Cases
   ├── types:    Types[]  (one per flat variant)
   └── variator: Types    (Pattern placeholders)
   │
   ▼ Parser::casesToTypes()
Types (rebuilt tree)
   │
   ▼ (string) cast
canonical ICU string
```

### Class hierarchy

```
DataTransferObjects/
├── Contracts/
│   ├── ICUTypeInterface          — base contract (Stringable + create + getAllVariants)
│   ├── ICUTypeVariableInterface  — getValue()
│   └── ICUTypeMergeInterface     — merge() (for adjacent Text/Pattern coalescing)
├── Types                         — immutable sequence of ICUTypeInterface
├── Variant                       — one flat branch (types + cases metadata)
├── Cases                         — flat variant set + variator
├── Pattern                       — raw text (implements ICUTypeMergeInterface)
├── Text                          — ICU-quoted literal (implements ICUTypeMergeInterface)
├── Variable                      — {name} or #
├── AbstractSelect                — base for branching types
│   ├── Select
│   ├── Plural
│   └── SelectOrdinal
├── Number
├── Date
├── Time
├── SpellOut
├── Duration
├── Ordinal
├── DateTimeFormat (enum)
├── Message                       — raw pattern/text sequence for format options
└── Number/
    ├── Skeleton
    ├── NumberNotation (abstract)
    │   ├── StandardNotation
    │   ├── NotationSimple
    │   ├── CompactShortNotation
    │   ├── CompactLongNotation
    │   ├── ScientificNotation
    │   └── EngineeringNotation
    ├── ScientificOptions
    ├── Format (enum)
    ├── Notation (enum)           — legacy; hierarchy classes are preferred
    ├── Sign (enum)
    ├── UnitWidth (enum)
    ├── Grouping (enum)
    ├── Precision (enum)
    ├── RoundingMode (enum)
    ├── DecimalSeparator (enum)
    ├── Currency
    ├── MeasureUnit
    ├── NumberingSystem
    ├── IntegerWidth
    ├── PrecisionFraction
    ├── PrecisionSignificant
    ├── PrecisionIncrement
    └── PrecisionFractional
```

---

## Running tests

```bash
composer install
./vendor/bin/phpunit --testdox
```

The test suite covers:

- All `Skeleton` token round-trips (parse → serialise) — 163 cases
- All enum values for `Sign`, `Format`, `Grouping`, `RoundingMode`, `DecimalSeparator`, `Precision`
- All constructor validation rules (`InvalidArgumentException` paths)
- `Types`: `map`, `filter`, `quote`, `replaceVariableName`, `setValues`, `getVariables`, `replaceRecursive`
- `Variant` merging — adjacent-node coalescing, case compatibility checks
- `Pattern`, `Text`, `Variable` serialisation and merging
- `Select`, `Plural`, `SelectOrdinal` — serialisation, `getAllVariants`, case metadata
- `Date`, `Time` — all format modes (named, skeleton, Message)
- `Number` — skeleton factory paths
- `SpellOut`, `Duration`, `Ordinal` — basic contract
- Integration — complex nested messages, full round-trips, variable extraction

---

## License

MIT © Eugene Erg
