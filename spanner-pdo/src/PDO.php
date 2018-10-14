<?php
/**
 * This file is part of Spanner-PDO for PHP.
 *
 * @license https://opensource.org/licenses/MIT MIT
 */
namespace SpannerPDO\Sql;

use SpannerPDO\Sql\Exception\PDOException;
use SpannerPDO\Sql\PDOStatement;
use SpannerPDO\Sql\Parser\SQLParser;
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
    use SQLParser;
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
        $dsnParts = self::parseDSN($dsn);

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
    private function parseDSN(string $dsn)
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
        $ret = 0;
        // Check CreateDDL or not
        if (preg_match('/create/i', $statement)) {
            $operation = $this->database->updateDdl($statement);
            $operation->pollUntilComplete();
            if ($operation->done()) {
                $ret = 1;
            }
        }
        //Database connection object
        $dbObj = null;
        if ($this->inTransaction()) {
            $dbObj = $this->transaction;
        } else {
            $dbObj = $this->database;
        }

            // Check Insert or not
        if (preg_match('/insert/i', $statement)) {
            $insertObj = $this->parseInsert($statement);
            $insertTransaction = $dbObj->insert(
                $insertObj['table'],
                $insertObj['data']
            );
            if ($insertTransaction) {
                    $ret = 1;
            }
        }

            // Check Insert or not
        if (preg_match('/update/i', $statement)) {
            $updateObj = $this->parseUpdate($statement);

            $primaryKeyColumns = $this->getPKeyFromTable($updateObj['table']);

            $sql = 'SELECT ';
            foreach ($primaryKeyColumns as $columName) {
                $sql = $sql . ' ' . $columName . ',';
            }
            $sql = trim($sql, ',');

            $updateRows = [];
            $results = $dbObj->execute($sql . ' FROM ' . $updateObj['table'] . ' WHERE ' . $updateObj['where']);
            foreach ($results->rows() as $row) {
                array_push($updateRows, array_merge($row, $updateObj['data']));
            }

            $insertTransaction = $dbObj->updateBatch($updateObj['table'], $updateRows);
            if ($insertTransaction) {
                $ret = count($updateRows);
            }
        }
        
        return $ret;
    }

    /**
     * Get table primay key
     *
     * @param string $tableName table name
     *
     * @return array Primary Key column name
     */
    private function getPKeyFromTable(string $tableName)
    {
        $results = $this->database->execute('SELECT * FROM ' . $tableName . ' LIMIT 1');
        foreach ($results->rows() as $row) {
        }

        $primaryKeyColumns = [];
        $primarykeyColumnNum = 0;
        $metadata = $results->metadata();
        foreach ($metadata['rowType']['fields'] as $meta) {
            if ($meta['type']['code'] === 2) {
                $primaryKeyColumnNum = array_push($primaryKeyColumns, $meta['name']);
            }
        }
    
        return $primaryKeyColumns;
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
    public function prepare($statement, $options = [])
    {
        return new PDOStatement($this, $statement, $options);
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
