<?php
/**
 *
 * User: Martin Halamíček
 * Date: 16.4.12
 * Time: 9:55
 *
 */

use Whiteplus\Csv\CsvText;
use Whiteplus\Csv\Csv;

class Whiteplus_CsvTextTest extends PHPUnit_Framework_TestCase
{

	public function getInstance($file_path, $delimiter = Csv::DEFAULT_DELIMITER, $enclosure = Csv::DEFAULT_ENCLOSURE, $escapedBy = "") {

		$file_contents = '';

		if (file_exists($file_path)) {
			$file_contents = file_get_contents($file_path);
		}

		return new CsvText($file_contents, $delimiter, $enclosure, $escapedBy);
	}

	public function testExistingFileShouldBeCreated()
	{
		$this->assertInstanceOf('Whiteplus\Csv\CsvText', $this->getInstance(__DIR__ . '/_data/test-input.csv'));
	}

//	public function testExceptionShouldBeThrownOnNotExistingFile()
//	{
//		$this->setExpectedException('Whiteplus\Csv\Exception');
//		$csv = $this->getInstance(__DIR__ . '/something.csv');
//		$csv->getHeader();
//	}

	public function testColumnsCount()
	{
		$csv = $this->getInstance(__DIR__ . '/_data/test-input.csv');

		$this->assertEquals(9, $csv->getColumnsCount());
	}

	/**
	 * @dataProvider validCsvTexts
	 * @param $fileName
	 */
	public function testRead($fileName, $delimiter)
	{
		$csvFile = $this->getInstance(__DIR__ . '/_data/' . $fileName, $delimiter, '"');

		$expected = array(
				"id",
				"idAccount",
				"date",
				"totalFollowers",
				"followers",
				"totalStatuses",
				"statuses",
				"kloutScore",
				"timestamp",
		);
		$this->assertEquals($expected, $csvFile->getHeader());
	}

	public function validCsvTexts()
	{
		return array(
			array('test-input.csv', ','),
			array('test-input.win.csv', ','),
			array('test-input.tabs.csv', "\t"),
			array('test-input.tabs.csv', "	"),
		);
	}

	public function testParse()
	{
		$csvFile = $this->getInstance(__DIR__ . '/_data/escaping.csv', ",", '"');

		$rows = array();
		foreach ($csvFile as $row) {
			$rows[] = $row;
		}

		$expected = array(
			array(
				'col1', 'col2',
			),
			array(
				'line without enclosure', 'second column',
			),
			array(
				'enclosure " in column', 'hello \\',
			),
			array(
				'line with enclosure', 'second column',
			),
			array(
				'column with enclosure ", and comma inside text', 'second column enclosure in text "',
			),
			array(
				"columns with\nnew line", "columns with\ttab",
			),
			array(
				"Columns with WINDOWS\r\nnew line", "second",
			),
			array(
				'column with \n \t \\\\', 'second col',
			),
		);

		$this->assertEquals($expected, $rows);
	}

	/**
	 * @dataProvider validJpCsvTexts
	 * @param $fileName
	 */
	public function testParseJp($fileName, $lineBreak, $encoding)
	{
		$csvFile = $this->getInstance(__DIR__ . '/_data/' . $fileName, ',', '"');

		$csvFile->setFileEncoding($encoding);
		$csvFile->setLineBreak($lineBreak);

		$rows = array();
		foreach ($csvFile as $row) {
			$rows[] = $row;
		}

		$expected = array(
			array("ID","名前","日付"),
            array("一","ホワイトプラス","2012年03月20日"),
            array("二","リネット","2012年03月21日")
		);

		$this->assertEquals($expected, $rows);
	}

	/**
	 * @dataProvider validJpCsvTexts
	 * @param $fileName
	 */
	public function testWriteJp($fileName, $lineBreak, $encoding)
	{
		$out = __DIR__ . '/_data/_out.csv';
		if (file_exists($out)) {
			unlink($out);
		}

		$csvFile = $this->getInstance($out, ',', '"');

		$csvFile->setFileEncoding($encoding);
		$csvFile->setLineBreak($lineBreak);

		$csvFile->writeRow( array("ID","名前","日付") );
		$csvFile->writeRow( array("一","ホワイトプラス","2012年03月20日") );
		$csvFile->writeRow( array("二","リネット","2012年03月21日") );

		file_put_contents($out, "{$csvFile}");

		$expected = file_get_contents(__DIR__ . '/_data/' . $fileName);
		$actual = file_get_contents($out);
		$this->assertEquals($expected, $actual);
	}

	public function validJpCsvTexts()
	{
		return array(
			array('test-input.jp_sjis.csv', "\r\n", 'SJIS-WIN'),
			array('test-input.jp_utf_8.csv', "\n", 'UTF-8'),
		);
	}


	public function testEmptyHeader()
	{
		$csvFile = $this->getInstance(__DIR__ . '/_data/test-input.empty.csv', ',', '"');

		$this->assertEquals(array(), $csvFile->getHeader());
	}

	/**
	 * @dataProvider invalidDelimiters
	 * @expectedException Whiteplus\Csv\InvalidArgumentException
	 * @param $delimiter
	 */
	public function testInvalidDelimiterShouldThrowException($delimiter)
	{
		$this->getInstance(__DIR__ . '/_data/test-input.csv', $delimiter);
	}
	public function invalidDelimiters()
	{
		return array(
			array('aaaa'),
			array('ob g'),
			array(''),
		);
	}

	public function testInitInvalidFileShouldNotThrowException()
	{
		try {
			$csvFile = $this->getInstance(__DIR__ . '/_data/dafadfsafd.csv');
		} catch (\Exception $e) {
			$this->fail('Exception should not be thrown');
		}
	}

	/**
	 * @dataProvider invalidEnclosures
	 * @expectedException Whiteplus\Csv\InvalidArgumentException
	 * @param $enclosure
	 */
	public function testInvalidEnclosureShouldThrowException($enclosure)
	{
		$this->getInstance(__DIR__ . '/_data/test-input.csv', ",", $enclosure);
	}

	public function invalidEnclosures()
	{
		return array(
			array('aaaa'),
			array('ob g'),
		);
	}

	/**
	 * @param $file
	 * @param $lineBreak
	 * @dataProvider validLineBreaksData
	 */
	public function testLineEndingsDetection($file, $lineBreak, $lineBreakAsText)
	{
		$csvFile = $this->getInstance(__DIR__ . '/_data/' . $file);
		$this->assertEquals($lineBreak, $csvFile->getLineBreak());
		$this->assertEquals($lineBreakAsText, $csvFile->getLineBreakAsText());
	}

	public function validLineBreaksData()
	{
		return array(
			array('test-input.csv', "\n",'\n'),
			array('test-input.win.csv', "\r\n", '\r\n'),
			array('escaping.csv', "\n", '\n'),
			array('just-header.csv', "\n", '\n'), // default
		);
	}

	/**
	 * @expectedException Whiteplus\Csv\InvalidArgumentException
	 * @dataProvider invalidLineBreaksData
	 */
	public function testInvalidLineBreak($file)
	{
		$csvFile = $this->getInstance(__DIR__ . '/_data/' . $file);
		$csvFile->validateLineBreak();
	}

	public function invalidLineBreaksData()
	{
		return array(
			array('test-input.mac.csv'),
		);
	}


	public function testWrite()
	{
		$fileName = __DIR__ . '/_data/_out.csv';
		if (file_exists($fileName)) {
			unlink($fileName);
		}

		$csvFile = $this->getInstance($fileName);

		$rows = array(
			array(
				'col1', 'col2',
			),
			array(
				'line without enclosure', 'second column',
			),
			array(
				'enclosure " in column', 'hello \\',
			),
			array(
				'line with enclosure', 'second column',
			),
			array(
				'column with enclosure ", and comma inside text', 'second column enclosure in text "',
			),
			array(
				"columns with\nnew line", "columns with\ttab",
			),
			array(
				'column with \n \t \\\\', 'second col',
			)
		);

		foreach ($rows as $row) {
			$csvFile->writeRow($row);
		}

	}

	public function testIterator()
	{
		$csvFile = $this->getInstance(__DIR__ . '/_data/test-input.csv');

		$expected = array(
			"id",
			"idAccount",
			"date",
			"totalFollowers",
			"followers",
			"totalStatuses",
			"statuses",
			"kloutScore",
			"timestamp",
		);

		// header line
		$csvFile->rewind();
		$this->assertEquals($expected, $csvFile->current());

		// first line
		$csvFile->next();
		$this->assertTrue($csvFile->valid());

		// second line
		$csvFile->next();
		$this->assertTrue($csvFile->valid());

		// file end
		$csvFile->next();
		$this->assertFalse($csvFile->valid());
	}

}
