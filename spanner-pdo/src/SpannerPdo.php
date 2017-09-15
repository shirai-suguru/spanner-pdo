<?php
/**
 * This file is part of Spanner-PDO for PHP.
 *
 * @license https://opensource.org/licenses/MIT MIT
 */
namespace SpannerPDO\Sql;

use SpannerPDO\Sql\Exception\PDOException;
use Google\Cloud\Spanner\SpannerClient;
use Google\Cloud\Spanner\Transaction;
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
    private $spannerClient;

    /**
     * @var Google\Cloud\Spanner\Instance
     */
    private $instance;

    /**
     * @var Google\Cloud\Spanner\Database
     */
    private $database;

    /**
     * @var Google\Cloud\Spanner\Transaction
     */
    private $transaction;

    /**
     * PDO constructer //TODO option forceCreateDB
     *
     * @param string $dsn      expect) spanner:instance={instanceId};dbname={databaseId}
     * @param string $username not use. for compatibility.
     * @param string $password not use. for compatibility.
     */
    public function __construct(string $dsn, string $username, string $password)
    {
        $dsnParts = self::_parseDSN($dsn);

        $this->spannerClient = new SpannerClient();
        $this->instance = $this->spannerClient->instance($dsnParts['instanceId']);
        $this->database = $this->instance->database($dsnParts['databaseId']);
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
        assert(!empty($this->database), "Database not found!");
        $this->transaction = $this->database->transaction();
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function commit()
    {
        $ret = false;
        if ($this->inTransaction()) {
            $commitTimeStamp = $this->transaction->commit();
            if (!empty($commitTimeStamp) && $this->transaction->state() === Transaction::STATE_COMMITTED) {
                $ret = true;
            }
        }
        return $ret;
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
        $ret = false;
        if (!empty($this->transaction) &&  $this->transaction->state() === Transaction::STATE_ACTIVE) {
            $ret = true;
        }
        return $ret;
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
        $ret = false;
        if ($this->inTransaction()) {
            $this->transaction->rollback();
            if ($this->transaction->state() === Transaction::STATE_ROLLED_BACK) {
                $ret = true;
            }
        }
        return $ret;
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
