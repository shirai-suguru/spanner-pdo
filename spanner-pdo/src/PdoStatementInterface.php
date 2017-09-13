<?php
/**
 * This file is part of Spanner-PDO for PHP.
 *
 * @license https://opensource.org/licenses/MIT MIT
 */
namespace SpannerPDO\Sql;

use PDO;

/**
 * An interface to the native PDOStatement object.
 *
 * @package SpannerPDO.Sql
 */
interface PdoStatementInterface
{
    // プロパティ
    // readonly string $queryString;

    /**
     * Bind a column to a PHP variable.
     *
     * @param mixed $column Number of the column (1-indexed)
     *                      or name of the column in the result set.
     * @param mixed $param  Name of the PHP variable to
     *                      which the column will be bound.
     *
     * @return bool True on success, false on failure.
     */
    public function bindColumn(mixed $column, mixed &$param);

    /**
     * Binds a parameter to the specified variable name.
     *
     * @param mixed $parameter Parameter identifier.
     * @param mixed $variable  Name of the PHP variable to bind
     *                         to the SQL statement parameter.
     * @param int   $data_type Explicit data type for the parameter
     *                         using the PDO::PARAM_* constants.
     *
     * @return bool True on success, false on failure.
     */
    public function bindParam(mixed $parameter, mixed &$variable, int $data_type = PDO::PARAM_STR);

    /**
     * Binds a value to a parameter.
     *
     * @param mixed $parameter Parameter identifier.
     * @param mixed $value     The value to bind to the parameter.
     * @param int   $data_type Explicit data type for the parameter
     *                         using the PDO::PARAM_* constants.
     *
     * @return bool True on success, false on failure.
     */
    public function bindValue(mixed $parameter, mixed $value, int $data_type = PDO::PARAM_STR);

    /**
     * Closes the cursor, enabling the statement to be executed again.
     *
     * @return bool True on success, false on failure.
     */
    public function closeCursor();

    /**
     * Returns the number of columns in the result set
     *
     * @return int Returns the number of columns in the result set represented by the PDOStatement object
     */
    public function columnCount();

    /**
     * Dump an SQL prepared command
     *
     * @return void
     */
    public function debugDumpParams();

    /**
     * Fetch the SQLSTATE associated with the last operation on the statement handle
     *
     * @return string Identical to PDO::errorCode(),
     *                except that PDOStatement::errorCode()
     *                only retrieves error codes for operations performed
     *                with PDOStatement objects.
     */
    public function errorCode();

    /**
     * Fetch extended error information associated with the last operation
     * on the statement handle
     *
     * @return array PDOStatement::errorInfo() returns an array of
     *               error information about the last operation performed
     *               by this statement handle.
     */
    public function errorInfo();

    /**
     * Executes a prepared statement
     *
     * @param array $input_parameters An array of values with as many elements
     *                                as there are bound parameters in the
     *                                SQL statement being executed.
     *                                All values are treated as PDO::PARAM_STR.
     *
     * @return bool True on success, false on failure.
     */
    public function execute(array $input_parameters = null);

    /**
     * Fetches the next row from a result set
     *
     * @param int $fetch_style        Controls how the next row will be returned
     *                                to the caller.
     *                                This value must be one of
     *                                the PDO::FETCH_* constants
     * @param int $cursor_orientation For a PDOStatement object representing
     *                                a scrollable cursor,
     *                                this value determines which row
     *                                will be returned to the caller.
     *
     * @return mixed The return value of this function on success
     *               depends on the fetch type.
     *               In all cases, FALSE is returned on failure
     */
    public function fetch(int $fetch_style = PDO::ATTR_DEFAULT_FETCH_MODE, int $cursor_orientation = PDO::FETCH_ORI_NEXT);

    /**
     * Returns an array containing all of the result set rows
     *
     * @param int   $fetch_style    Controls how the next row will be returned
     *                              to the caller.
     *                              This value must be one of
     *                              the PDO::FETCH_* constants
     * @param mixed $fetch_argument This argument has a different meaning
     *                              depending on the value of the fetch_style parameter:
     *                              PDO::FETCH_COLUMN: Returns the indicated 0-indexed column.
     *                              PDO::FETCH_CLASS: Returns instances of the specified class,
     *                              mapping the columns of each row to named properties in the class.
     *                              PDO::FETCH_FUNC: Returns the results of calling the specified function,
     *                              using each row's columns as parameters in the call.
     *
     * @return array PDOStatement::fetchAll() returns an array containing all of the remaining rows in the result set.
     */
    public function fetchAll(int $fetch_style, mixed $fetch_argument);

    /**
     * Returns a single column from the next row of a result set
     *
     * @param int $column_number 0-indexed number of the column
     *                           you wish to retrieve from the row.
     *                           If no value is supplied,
     *                           PDOStatement::fetchColumn() fetches the first column.
     *
     * @return mixed PDOStatement::fetchColumn() returns a single column
     *               from the next row of a result set or FALSE if there are no more rows.
     */
    public function fetchColumn(int $column_number = 0);
    
    /**
     * Fetches the next row and returns it as an object.
     *
     * @param string $class_name Name of the created class.
     *
     * @return mixed Returns an instance of the required class
     *               with property names that correspond
     *               to the column names or FALSE on failure.
     */
    public function fetchObject(string $class_name = "stdClass");

    /**
     * Retrieve a statement attribute
     *
     * @param int $attribute Gets an attribute of the statement.
     *
     * @return mixed Returns the attribute value.
     */
    public function getAttribute(int $attribute);
    
    /**
     * Returns metadata for a column in a result set
     *
     * @param int $column The 0-indexed column in the result set.
     *
     * @return array Returns an associative array containing
     *               the following values representing
     *               the metadata for a single column
     */
    public function getColumnMeta(int $column);

    /**
     * Advances to the next rowset in a multi-rowset statement handle
     *
     * @return bool True on success, false on failure.
     */
    public function nextRowset();
    
    /**
     * Returns the number of rows affected by the last SQL statement
     *
     * @return int Returns the number of rows.
     */
    public function rowCount();

    /**
     * Returns metadata for a column in a result set
     *
     * @param int   $attribute The 0-indexed column
     * @param mixed $value     set value
     *
     * @return bool Returns TRUE on success or FALSE on failure.
     */
    public function setAttribute(int $attribute, mixed $value);
    
    /**
     * Returns metadata for a column in a result set
     *
     * @param int $mode The fetch mode must be one of the PDO::FETCH_* constants.
     *
     * @return bool Returns TRUE on success or FALSE on failure.
     */
    public function setFetchMode(int $mode);
}
