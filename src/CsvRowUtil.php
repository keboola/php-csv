<?php
/**
 * TODO:
 *
 */

namespace Keboola\Csv;


trait CsvRowUtil
{
    /**
     * @param array $row
     * @return string
     * @throws Exception
     */
    public function rowToStr(array $row)
    {
        $return = [];
        foreach ($row as $column) {
            if (!(
                is_scalar($column)
                || is_null($column)
                || (
                    is_object($column)
                    && method_exists($column, '__toString')
                )
            )) {
                throw new Exception(
                    "Cannot write data into column: " . var_export($column, true),
                    Exception::WRITE_ERROR
                );
            }

            $return[] = $this->getEnclosure() .
                str_replace($this->getEnclosure(), str_repeat($this->getEnclosure(), 2), $column) .
                $this->getEnclosure();
        }
        return implode($this->getDelimiter(), $return) . $this->getLineBreak();
    }

    /**
     * TODO:
     *
     * @return mixed
     */
    abstract protected function getDelimiter();

    /**
     * TODO:
     *
     * @return mixed
     */
    abstract protected function getLineBreak();

    /**
     * TODO:
     *
     * @return mixed
     */
    abstract protected function getEnclosure();

}