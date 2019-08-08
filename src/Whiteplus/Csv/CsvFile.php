<?php
/**
 * Created by IntelliJ IDEA.
 * User: kai
 * Date: 12/19/14
 * Time: 5:57 PM
 */

namespace Whiteplus\Csv;

class CsvFile extends Csv {

    protected $_fileName;
    protected $_filePointer;

    public function __construct(
        $fileName, $delimiter = self::DEFAULT_DELIMITER, $enclosure = self::DEFAULT_ENCLOSURE, $escapedBy = "") {

        parent::__construct($delimiter, $enclosure, $escapedBy);
        $this->_fileName = $fileName;
    }

    public function __destruct()
    {
        if (is_resource($this->_filePointer)) {
            fclose($this->_filePointer);
        }
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     */
    public function rewind() {
        rewind($this->_getFilePointer());
        parent::rewind();
    }

    protected function _getLineBreakDetectionSample()
    {

        $sample = '';
        $file = new \SplFileInfo($this->_fileName);

        if (is_file($file->getPathname())) {

            rewind($this->_getFilePointer());
            $sample = fread($this->_getFilePointer(), 10000);
            rewind($this->_getFilePointer());
        }

        return $sample;
    }

    protected function _writeLine($row) {

        $ret = fwrite($this->_getFilePointer('w+'), $row);

        /* According to http://php.net/fwrite the fwrite() function
         should return false on error. However not writing the full
         string (which may occur e.g. when disk is full) is not considered
         as an error. Therefore both conditions are necessary. */
        if (($ret === false) || (($ret === 0) && (strlen($row) > 0)))  {
            throw new Exception("Cannot open file {$this->_file}",
                Exception::WRITE_ERROR, NULL, 'writeError');
        }
    }

    protected function _readLine() {
        if ($this->getFileEncoding() == 'UTF-16LE') {
            $line = stream_get_line($this->_getFilePointer(), $this->getLineLength(), $this->_getLineSeparator());
            if (substr($line, -2) == "\x0D\x00") {
                // for CRLF
                $this->_setLineSeparator("\x0D\x00" . $this->_getLineSeparator());
                $line = substr($line, 0, strlen($line) - 2);
            }
            if ($this->_rowCounter == 0) {
                $line = preg_replace("/^\xFF\xFE/", "", $line);
            }

            return $line;
        }

        $line = fgets($this->_getFilePointer());

        if ($this->_rowCounter == 0) {
            $line = preg_replace("/^\xEF\xBB\xBF/", "", $line);
        }

        return $line;
    }

    protected function _getFilePointer($mode = 'r')
    {
        if (!is_resource($this->_filePointer)) {
            $this->_openFile($mode);
        }
        return $this->_filePointer;
    }

    protected function _openFile($mode)
    {
        $file = new \SplFileInfo($this->_fileName);

        if ($mode == 'r' && !is_file($file->getPathname())) {
            throw new Exception("Cannot open file {$file}",
                Exception::FILE_NOT_EXISTS, NULL, 'fileNotExists');
        }

        $this->_filePointer = fopen($file->getPathname(), $mode);

        if (!$this->_filePointer) {
            throw new Exception("Cannot open file {$file}",
                Exception::FILE_NOT_EXISTS, NULL, 'fileNotExists');
        }
    }

    /**
     * (PHP 5 &gt;= 5.1.2)<br/>
     * Returns the path to the file as a string
     * @link http://php.net/manual/en/splfileinfo.tostring.php
     * @return string the path to the file.
     */
    public function __toString () {
        return file_get_contents($this->_fileName);
    }
}
