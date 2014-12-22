<?php
/**
 *
 * User: Martin Halamíček
 * Date: 13.4.12
 * Time: 15:31
 *
 */

namespace Whiteplus\Csv;

abstract class Csv implements \Iterator
{
	const DEFAULT_DELIMITER = ',';
	const DEFAULT_ENCLOSURE = '"';

	protected $_delimiter;
	protected $_enclosure;
	protected $_escapedBy;

	protected $_rowCounter = 0;
	protected $_currentRow;
	protected $_firstRow;
	protected $_lineBreak;

	protected $_encoder;
	protected $_decoder;

	public function __construct(
		$delimiter = self::DEFAULT_DELIMITER, $enclosure = self::DEFAULT_ENCLOSURE, $escapedBy = "") {

		$this->_escapedBy = $escapedBy;

		// set delimiter
		if (strlen($delimiter) > 1) {
			throw new InvalidArgumentException("Delimiter must be a single character. \"$delimiter\" received",
				Exception::INVALID_PARAM, NULL, 'invalidParam');
		}

		if (strlen($delimiter) == 0) {
			throw new InvalidArgumentException("Delimiter cannot be empty.",
				Exception::INVALID_PARAM, NULL, 'invalidParam');
		}

		$this->_delimiter = $delimiter;

		// set enclosure
		if (strlen($enclosure) > 1) {
			throw new InvalidArgumentException("Enclosure must be a single character. \"$enclosure\" received",
				Exception::INVALID_PARAM, NULL, 'invalidParam');
		}

		$this->_enclosure = $enclosure;
	}

	public function getDelimiter()
	{
		return $this->_delimiter;
	}

	public function getEnclosure()
	{
		return $this->_enclosure;
	}

	public function getEscapedBy()
	{
		return $this->_escapedBy;
	}

	public function getColumnsCount()
	{
		return count($this->getHeader());
	}

	public function getHeader()
	{
		$this->rewind();
		$current = $this->current();
		if (is_array($current)) {
			return $current;
		}

		return array();
	}

	public function setLineBreak($lineBreak) {
		$this->_lineBreak = $lineBreak;
	}

	public function setFileEncoding($file_encoding) {

		$system_encoding = mb_internal_encoding();
		if ($system_encoding == 'ISO-8859-1') {
			$system_encoding = 'UTF-8';
		}

		if ($file_encoding != $system_encoding) {

            $this->_encoder = function ($line) use ($system_encoding, $file_encoding) {
                return mb_convert_encoding($line, $file_encoding, $system_encoding);
            };

            $this->_decoder = function ($line) use ($system_encoding, $file_encoding) {
                return mb_convert_encoding($line, $system_encoding, $file_encoding);
            };
		}
	}

	public function writeRow(array $row)
	{
		$line = $this->rowToStr($row);
		if ($this->_encoder) {
			$line =  call_user_func($this->_encoder,$line);
		}
		$this->_writeLine($line);
	}

	protected function readRow() {

		// FIXME エスケープに対応してない、fgetscsvの実装を参考に対応する

		$this->validateLineBreak();

		// allow empty enclosure hack
		$enclosure = !$this->getEnclosure() ? chr(0) : $this->getEnclosure();
		$escapedBy = !$this->_escapedBy ? chr(0) : $this->_escapedBy;
		$delimiter = $this->getDelimiter();

		$row = FALSE;
		$column = '';
		$enclosed = false;
		$prev_char = '';

		while (TRUE) {

			// FIXME enclosedで改行をまたいでEOFにぶつかると最終行が消失するので修正する
			// detect EOF
			if (($line = $this->_readLine()) === FALSE) {
				return $row;
			}

			if ($this->_decoder) {
				$line =  call_user_func($this->_decoder,$line);
			}

			// chomp
			$br = $this->getLineBreak();
			$br = mb_substr($line, mb_strlen($line) - strlen($br));
			if ($br == $this->getLineBreak()) {
				$line = mb_substr($line, 0, mb_strlen($line) - strlen($br));
			}

			for ($i = 0; $i < mb_strlen($line); $i++) {

				$char = mb_substr($line, $i, 1);

				if ($enclosed) {

					// end enclosing
					if ($char == $enclosure) {
						$enclosed = false;
						$prev_char = $char;
						continue;
					}

				} else {

					$trimmed = trim($column);

					// start enclosing
					if ($char == $enclosure) {

						if ($prev_char == $enclosure) {
							// 直前の文字がenclosureの場合はエスケープ済みとしてカラムに入れて復帰
							$enclosed = true;
						} else if (empty($trimmed)) {
							$column = '';
							$enclosed = true;
							$prev_char = $char;
							continue;
						}
					}

					// start column
					if ($char == $delimiter) {
						if (!$row) {
							$row = [];
						}
						$row[] = $column;
						$column = '';
						$prev_char = $char;
						continue;
					}
				}

				$column .= $char;
				$prev_char = $char;
			}

			// クォートの中にいる場合は次の行をまたいで読み込む
			if ($enclosed) {
				$column .= $this->getLineBreak();
				continue;
			}

			if (!empty($column)) {
				if (!$row) {
					$row = [];
				}
				$row[] = $column;
			}

			break;
		}

		return $row;
	}

	public function rowToStr(array $row)
	{
		$return = array();
		foreach ($row as $column) {
			$return[] = $this->getEnclosure()
				. str_replace($this->getEnclosure(), str_repeat($this->getEnclosure(), 2), $column) . $this->getEnclosure();
		}
		return implode($this->getDelimiter(), $return) . $this->getLineBreak();
	}

	public function getLineBreak()
	{
		if (!$this->_lineBreak) {
			$this->_lineBreak = $this->_detectLineBreak();
		}
		return $this->_lineBreak;
	}

	public function getLineBreakAsText()
	{
		return trim(json_encode($this->getLineBreak()), '"');
	}

	public function validateLineBreak()
	{
		$lineBreak = $this->getLineBreak();
		if (in_array($lineBreak, array("\r\n", "\n"))) {
			return $lineBreak;
		}

		throw new InvalidArgumentException("Invalid line break. Please use unix \\n or win \\r\\n line breaks.",
			Exception::INVALID_PARAM, NULL, 'invalidParam');
	}

	protected  function _detectLineBreak()
	{
		$sample = $this->_getLineBreakDetectionSample();

		$possibleLineBreaks = array(
			"\r\n", // win
			"\r", // mac
			"\n", // unix
		);

		$lineBreaksPositions = array();
		foreach($possibleLineBreaks as $lineBreak) {
			$position = strpos($sample, $lineBreak);
			if ($position === false) {
				continue;
			}
			$lineBreaksPositions[$lineBreak] = $position;
		}


		asort($lineBreaksPositions);
		reset($lineBreaksPositions);

		return empty($lineBreaksPositions) ? "\n" : key($lineBreaksPositions);
	}

	/**
	 * (PHP 5 &gt;= 5.1.0)<br/>
	 * Return the current element
	 * @link http://php.net/manual/en/iterator.current.php
	 * @return mixed Can return any type.
	 */
	public function current()
	{
		return $this->_currentRow;
	}

	/**
	 * (PHP 5 &gt;= 5.1.0)<br/>
	 * Move forward to next element
	 * @link http://php.net/manual/en/iterator.next.php
	 * @return void Any returned value is ignored.
	 */
	public function next()
	{
		$this->_currentRow = $this->readRow();
		$this->_rowCounter++;

		if (!$this->_firstRow) {
			$this->_firstRow = $this->_currentRow;
		}
	}

	/**
	 * (PHP 5 &gt;= 5.1.0)<br/>
	 * Return the key of the current element
	 * @link http://php.net/manual/en/iterator.key.php
	 * @return scalar scalar on success, integer
	 * 0 on failure.
	 */
	public function key()
	{
		return $this->_rowCounter;
	}

	/**
	 * (PHP 5 &gt;= 5.1.0)<br/>
	 * Checks if current position is valid
	 * @link http://php.net/manual/en/iterator.valid.php
	 * @return boolean The return value will be casted to boolean and then evaluated.
	 * Returns true on success or false on failure.
	 */
	public function valid()
	{
		return $this->_currentRow !== false;
	}

	/**
	 * (PHP 5 &gt;= 5.1.0)<br/>
	 * Rewind the Iterator to the first element
	 * @link http://php.net/manual/en/iterator.rewind.php
	 * @return void Any returned value is ignored.
	 */
	public function rewind()
	{
		if (!$this->_firstRow) {
			$this->_firstRow = $this->readRow();
		}

		$this->_currentRow = $this->_firstRow;
		$this->_rowCounter = 0;
	}

	abstract protected function _getLineBreakDetectionSample();
	abstract protected function _writeLine($row);
	abstract protected function _readLine();
}
