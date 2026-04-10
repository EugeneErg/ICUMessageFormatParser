# ICU MessageFormat Parser for PHP

A lightweight PHP library for parsing ICU MessageFormat strings into structured objects and transforming complex branching messages into linear cases.

## ✨ Features

* Parse ICU MessageFormat strings into an object tree
* Work with strongly-typed message elements (plural, select, time, etc.)
* Transform branching patterns (`select`, `plural`, `selectordinal`) into flat case sets
* Convert back from generated cases into structured message types
* Utility methods for quoting and escaping

---

## 📦 Installation

```bash
composer require eugene-erg/icu-message-format-parser
```

---

## 🚀 Basic Usage

### Parsing a message

```php
use EugeneErg\ICUMessageFormatParser\Parser;

$parser = new Parser();

$types = $parser->parse(
    '{gender, select, male {He} female {She} other {They}} liked this.'
);
```

The result is a `Types` object containing a structured representation of the message.

---

### Working with cases (flattening branching logic)

```php
use EugeneErg\ICUMessageFormatParser\Parser;

$parser = new Variator();

$cases = $parser->typesToCases($types);
```

This converts a branching ICU message into a list of linear message variations.

Example result (conceptually):

```
male   → "He liked this."
female → "She liked this."
other  → "They liked this."
```

---

### Rebuilding types from cases

```php
$types = $variator->casesToTypes($cases);
```

---

### Quoting text

```php
$text = $parser->quote("This {must} be escaped");
```

---

## 🧠 Core Concepts

### Types

`Types` is a container for parsed message elements:

```php
new Types(array $types)
```

Each element implements `ICUTypeInterface`.

---

### Cases

Represents flattened variations of a message:

```php
new Cases(array $types, Types $variator)
```

* `types`: array of linear message variants
* `variator`: the original branching structure

---

### Supported ICU Elements

The library provides typed representations for ICU constructs:

* `Select`
* `Plural`
* `SelectOrdinal`
* `Time`
* `SpellOut`
* and more...

Example:

```php
new Time('createdAt', DateTimeFormat::Medium);
```

---

## 🔄 Transformation Flow

```
ICU string
   ↓ parse()
Types (tree structure)
   ↓ typesToCases()
Cases (flat variations)
   ↓ casesToTypes()
Types (rebuilt structure)
```

---

## 🧩 Example

```php
$message = '{count, plural, one {1 item} other {# items}}';

$types = $parser->parse($message);
$cases = $variator->typesToCases($types);
```

Result:

```
one   → "1 item"
other → "X items"
```

---

## 🎯 Use Cases

* Localization tooling
* Generating all possible message variations
* Testing translations
* Static analysis of ICU messages
* Building translation UIs

---

## 📄 License

MIT
