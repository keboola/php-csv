# Keboola CSV reader/writer
[![Latest Stable Version](https://poser.pugx.org/keboola/csv/v/stable.svg)](https://packagist.org/packages/keboola/csv)
[![License](https://poser.pugx.org/keboola/csv/license.svg)](https://packagist.org/packages/keboola/csv)
[![Total Downloads](https://poser.pugx.org/keboola/csv/downloads.svg)](https://packagist.org/packages/keboola/csv)

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
$csvFile = new \Keboola\Csv\CsvFile(
    $fileName,
    CsvFile::DEFAULT_DELIMITER,
    CsvFile::DEFAULT_ENCLOSURE,
    CsvFile::DEFAULT_ESCAPED_BY,
    1
)
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

### Write CSV With Windows new-lines

```php
$csvFile = new Keboola\Csv\CsvWriter(
    'test-output.csv',
    CsvWriter::DEFAULT_DELIMITER,
    CsvWriter::DEFAULT_ENCLOSURE,
    "\r\n"
)
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


## Development

Clone this repository and init the workspace with following command:

```
git clone https://github.com/keboola/php-csv.git
cd php-csv
docker-compose build
docker-compose run --rm dev composer install --no-scripts
```

Run the test suite using this command:

```
docker-compose run --rm dev composer tests
```
## License

MIT licensed, see [LICENSE](./LICENSE) file.
