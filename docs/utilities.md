# BASE3 Utility Classes

## Purpose

This document records the small utility classes currently shipped in `src/Util`.

These classes are not broad framework extension points. They are concrete helpers with legacy-compatible APIs.

The current utility areas are:

```text
Chronos
MailParser
```

---

## 1. `Chronos`

Class:

```php
Base3\Util\Chronos\Chronos
```

`Chronos` is a mutable date/time helper that stores individual date/time fields and normalizes them through PHP `DateTime`.

It is not a replacement for all `DateTime` or `DateTimeImmutable` use.

---

## 2. Creating a `Chronos` value

Use:

```php
$time = Chronos::create(
	2026,
	8,
	18,
	14,
	30,
	0
);
```

The constructor is not the public initialization API. `create()` sets all components through the fluent setters.

---

## 3. Date/time setters

The class provides:

```text
setYear
setMonth
setDay
setHour
setMinute
setSecond
```

Each setter returns `$this`.

Only integer values are applied.

After each update the class normalizes the complete date/time through `DateTime`.

That means values outside a normal field range may roll into adjacent date/time fields according to PHP `DateTime` normalization.

---

## 4. Date/time addition

The class provides:

```text
addYears
addMonths
addDays
addHours
addMinutes
addSeconds
```

Each method modifies the stored component, normalizes the date, and returns `$this`.

This is mutable behavior.

Do not assume immutable date semantics.

---

## 5. Formatting

```php
$text = $time->format('Y-m-d H:i:s');
```

`format()` creates a PHP `DateTime`, applies the stored fields, and delegates to `DateTime::format()`.

---

## 6. Accessors

`Chronos` provides:

```text
getYear
getMonth
getDay
getHour
getMinute
getSecond
```

Each value is returned as an integer.

---

## 7. `Chronos` scope

Use this helper where existing BASE3 code expects its mutable field-based behavior.

For new APIs that need richer timezone, interval, immutable, or standards-based date semantics, define the requirement explicitly rather than extending `Chronos` into an unrelated date framework.

---

## 8. `MailParser`

Class:

```php
Base3\Util\MailParser\MailParser
```

`MailParser` is a lightweight MIME-like multipart parser for raw mail content.

It can be initialized from a file:

```php
$parser = new MailParser($filename);
```

or populated from a string:

```php
$parser = new MailParser();
$parser->fromString($content);
```

---

## 9. Multipart parsing

The parser searches the content for a multipart `Content-Type` boundary.

When a boundary is found it:

* removes the closing boundary section
* splits the message into segments
* recursively parses each segment

When no multipart boundary is found, it returns a `MailPart` leaf object.

The resulting structure may therefore be nested arrays containing `MailPart` leaves.

---

## 10. Getting parsed parts

```php
$parts = $parser->getParts();
```

The return value is intentionally broad because it may be:

```text
MailPart
array of nested parts
```

This is a lightweight parser, not a typed MIME object model.

---

## 11. Debug representation

`MailParser::toString()` recursively renders a simple structural text representation of the parsed part tree.

It is primarily useful for diagnostics and tests.

---

## 12. `MailPart`

A leaf mail part is represented by:

```php
Base3\Util\MailParser\MailPart
```

It separates:

```text
headers
body
```

Header continuation lines beginning with whitespace are joined to the previous header line.

---

## 13. Mail headers

`getHeaders()` returns a list of structures containing:

```text
name
value
```

The parser preserves the parsed header order rather than converting headers into a unique associative map.

That matters because mail formats may contain repeated header names.

---

## 14. Mail body

`getBody()` returns the body text after the first blank line separating headers and body.

`isEmpty()` checks whether the parsed body length is zero.

---

## 15. Limitations of the current mail parser

The utility does not claim to be a complete RFC-compliant MIME library.

The current code focuses on:

* multipart boundary splitting
* header/body separation
* folded header lines
* recursive structural parsing

It does not provide a documented general API for:

* transfer-encoding decoding
* charset conversion
* attachment extraction
* content-disposition interpretation
* address parsing
* signature verification

If a plugin needs a full MIME implementation, use an appropriate dependency or plugin-specific abstraction rather than silently expanding this utility into a second mail framework.

---

## 16. Utility test pages

The source tree currently contains `ChronosTest` and `MailParserTest` classes under the utility directories.

They implement page-oriented test/demo behavior and are not substitutes for the PHPUnit test suite.

Repository-level automated testing is documented in `developer-tooling.md`.

---

## 17. Summary

```text
Chronos
  mutable normalized date/time helper

MailParser
  recursive multipart parser

MailPart
  leaf headers/body representation
```

Treat these as concrete utilities with the behavior documented above, not as framework-wide extension slots.
