<?php
/**
 * This file is part of Spanner-PDO for PHP.
 *
 * @license https://opensource.org/licenses/MIT MIT
 */
namespace SpannerPDO\Sql;

use SpannerPDO\Sql\Exception\PDOException;
use Google\Cloud\Spanner\SpannerClient;
use PDO as BasePDO;

/**
 * An PDO object for spanner.
 *
 * @package SpannerPDO.sql
 */
class PDO implements PDOInterface
{
    const DSN_REGEX = '/^spanner:instance=([\w\d.-]+);dbname=([\w\d.-]+)/';

    /**
     * @var Google\Cloud\Spanner\SpannerClient
     */
    private $_spannerClient;

    /**
     * @var Google\Cloud\Spanner\Instance
     */
    private $_instance;

    /**
     * @var Google\Cloud\Spanner\Database
     */
    private $_database;

    /**
     * PDO constructer
     *
     * @param string $dsn      expect) spanner:instance={instanceId};dbname={databaseId}
     * @param string $username not use. for compatibility.
     * @param string $password not use. for compatibility.
     */
    public function __construct(string $dsn, string $username, string $password)
    {
        $dsnParts = self::_parseDSN($dsn);

        $this->_spannerClient = new SpannerClient();
        $this->_instance = $this->_spannerClient->instance($dsnParts['instanceId']);
        $this->_database = $this->_instance->database($dsnParts['databaseId']);
    }

    /**
     * Extract instanceId and databaseId from DSN string for spanner
     *
     * @param string $dsn The DSN string
     *
     * @return array An array of ['instanceId' => instanceId,'databaseId' => databaseId]
     */
    private function _parseDSN(string $dsn)
    {
        $matches = array();

        // check string 'spanner'
        if (!preg_match(static::DSN_REGEX, $dsn, $matches)) {
            throw new PDOException(sprintf('Invalid DSN %s', $dsn));
        }

        return ['instanceId' => $matches[1], 'databaseId' => $matches[2]];
    }
    /**
     * {@inheritDoc}
     */
    public function beginTransaction()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function commit()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function errorCode()
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function errorInfo()
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function exec($statement)
    {
        return 1;
    }

    /**
     * {@inheritDoc}
     */
    public function getAttribute($attribute)
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function inTransaction()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function lastInsertId($name = null)
    {
        return "";
    }

    /**
     * {@inheritDoc}
     */
    public function prepare($statement, $options = null)
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function query($statement, ...$fetch)
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function quote($value, $parameter_type = PDO::PARAM_STR)
    {
        return "";
    }

    /**
     * {@inheritDoc}
     */
    public function rollBack()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function setAttribute($attribute, $value)
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public static function getAvailableDrivers()
    {
        return [];
    }
}
