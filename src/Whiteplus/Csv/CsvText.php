<?php
/**
 * Created by IntelliJ IDEA.
 * User: kai
 * Date: 12/19/14
 * Time: 5:57 PM
 */

namespace Whiteplus\Csv;

class CsvText extends Csv {

    protected $_text;
    protected $_lines;
    protected $_index = 0;

    public function __construct(
        $text = '', $delimiter = self::DEFAULT_DELIMITER, $enclosure = self::DEFAULT_ENCLOSURE, $escapedBy = "") {

        parent::__construct($delimiter, $enclosure, $escapedBy);
        $this->_text = $text;
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     */
    public function rewind() {
        $this->_index = 0;
        parent::rewind();
    }

    protected function _getLineBreakDetectionSample() {
        return $this->_text;
    }

    protected function _writeLine($row) {

        if (!$this->_lines) {
            $this->_lines = [];
        }

        $this->_lines[] = $row;
    }

    protected function _readLine() {

        if (!$this->_lines) {
            $this->_lines = explode($this->getLineBreak(), $this->_text);
        }

        if ($this->_index < count($this->_lines)) {
            $current = $this->_index;
            $this->_index++;
            return $this->_lines[$current] . $this->getLineBreak();
        }

        return FALSE;
    }

    /**
     * (PHP 5 &gt;= 5.1.2)<br/>
     * Returns the path to the file as a string
     * @link http://php.net/manual/en/splfileinfo.tostring.php
     * @return string the path to the file.
     */
    public function __toString () {

        if (!$this->_lines) {
            return '';
        }

        return implode('', $this->_lines);
    }
}