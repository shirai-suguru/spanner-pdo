<?php
/**
 * This file is part of Spanner-PDO for PHP.
 *
 * @license https://opensource.org/licenses/MIT MIT
 */
namespace SpannerPDO\Sql;

use IteratorAggregate;
use Traversable;

class PDOStatement implements PDOStatementInterface, IteratorAggregate
{
    /**
     * PDOStatement Object
     *
     * @param PDOInterface $pdo     Spanner PDO Object
     * @param string       $sql     SQL string
     * @param array        $options option
     */
    public function __construct(PDOInterface $pdo, $sql, array $options)
    {
        $this->sql     = $sql;
        $this->pdo     = $pdo;
        $this->options = array_merge($this->options, $options);
    }
    
    /**
     * {@Inheritdoc}
     */
    public function getIterator()
    {
        return new ArrayIterator($this->fetchAll());
    }

    public function bindColumn(mixed $column, mixed &$param)
    {
        return true;
    }

    public function bindParam(mixed $parameter, mixed &$variable, int $data_type = PDO::PARAM_STR)
    {
        return true;
    }

    public function bindValue(mixed $parameter, mixed $value, int $data_type = PDO::PARAM_STR)
    {
        return true;
    }

    public function closeCursor()
    {
        return true;
    }

    public function columnCount()
    {
        return 1;
    }

    public function debugDumpParams()
    {
    }

    public function errorCode()
    {
        return "";
    }

    public function errorInfo()
    {
        return [];
    }

    public function execute(array $input_parameters = null)
    {
        return true;
    }

    public function fetch(int $fetch_style = PDO::ATTR_DEFAULT_FETCH_MODE, int $cursor_orientation = PDO::FETCH_ORI_NEXT)
    {
        return false;
    }

    public function fetchAll(int $fetch_style, mixed $fetch_argument)
    {
        return [];
    }

    public function fetchColumn(int $column_number = 0)
    {
        return false;
    }

    public function fetchObject(string $class_name = "stdClass")
    {
        return false;
    }

    public function getAttribute(int $attribute)
    {
        return 1;
    }

    public function getColumnMeta(int $column)
    {
        return [];
    }

    public function nextRowset()
    {
        return true;
    }

    public function rowCount()
    {
        return 1;
    }

    public function setAttribute(int $attribute, mixed $value)
    {
        return true;
    }

    public function setFetchMode(int $mode)
    {
        return true;
    }
}
