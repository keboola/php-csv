# Keboola CSV reader/writer [![Build Status](https://secure.travis-ci.org/keboola/php-csv.png)](http://travis-ci.org/keboola/php-csv)
[![Latest Stable Version](https://poser.pugx.org/keboola/csv/v/stable.svg)](https://packagist.org/packages/keboola/csv)
[![License](https://poser.pugx.org/keboola/csv/license.svg)](https://packagist.org/packages/keboola/csv)
[![Total Downloads](https://poser.pugx.org/keboola/csv/downloads.svg)](https://packagist.org/packages/keboola/csv)
[![Maintainability](https://api.codeclimate.com/v1/badges/869a0ab5c1d228279ab0/maintainability)](https://codeclimate.com/github/keboola/php-csv/maintainability)
[![Test Coverage](https://api.codeclimate.com/v1/badges/869a0ab5c1d228279ab0/test_coverage)](https://codeclimate.com/github/keboola/php-csv/test_coverage)

The library provides a simple reader and writer for CSV files according to [RFC4180](https://tools.ietf.org/html/rfc4180). 
The library is licensed under the [MIT](https://github.com/keboola/php-csv/blob/master/LICENSE) license. The library provides 
classes `CsvReader` and `CsvWriter` for reading and writing CSV files. The classes are designed to be **immutable** 
and minimalistic.

## Usage

### Read CSV

```php
$csvFile = new Keboola\Csv\CsvReader(__DIR__ . '/_data/test-input.csv');
foreach($csvFile as $row) {
	var_dump($row);
}
```

#### Skip lines
Skip the first line:

```php
$csvFile = new \Keboola\Csv\CsvFile($fileName, CsvFile::DEFAULT_DELIMITER, CsvFile::DEFAULT_ENCLOSURE, CsvFile::DEFAULT_ESCAPED_BY, 1)
foreach($csvFile as $row) {
	var_dump($row);
}
```
      

### Write CSV

```php
$csvFile = new Keboola\Csv\CsvWriter(__DIR__ . '/_data/test-output.csv');
$rows = [
	[
		'col1', 'col2',
	],
	[
		'first column', 'second column',
	],
];

foreach ($rows as $row) {
	$csvFile->writeRow($row);
}
```

### Append to CSV

```php
$fileName = __DIR__ . '/_data/test-output.csv';
$file = fopen($fileName, 'a');
$csvFile = new Keboola\Csv\CsvWriter($file);
$rows = [
	[
		'col1', 'col2',
	],
	[
		'first column', 'second column',
	],
];

foreach ($rows as $row) {
	$csvFile->writeRow($row);
}
fclose($file);
```

## Installation

The library is available as [composer package](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx). 
To start using this library in your project follow these steps:

**Install package:**

```bash
composer require keboola/csv
```


**Add autoloader in your bootstrap script:**

```bash
require 'vendor/autoload.php';
```

Read more in [Composer documentation](http://getcomposer.org/doc/01-basic-usage.md)
