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
    const INSERT_REGEX = '/^[i|I][n|N][s|S][e|E][r|R][t|T]\s+[i|I][n|N][t|T][o|O]\s+([\w\d-]+)\s*([\w\d\W]*)values\s*([\w\d\W]*)/';

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
        $ret = 0;
        // Check CreateDDL or not
        if (preg_match('/create/i', $statement)) {
            $operation = $this->database->updateDdl($statement);
            $operation->pollUntilComplete();
            if ($operation->done()) {
                $ret = 1;
            }
        }
        // Check Insert or not
        if (preg_match('/insert/i', $statement)) {
            $insertObj = $this->parseInsert($statement);
            if ($this->inTransaction()) {
                $insertTransaction = $this->transaction->insert(
                    $insertObj['table'],
                    $insertObj['data']
                );
                if ($insertTransaction) {
                    $ret = 1;
                }
            } else {
                $insertTimeStamp = $this->database->insert(
                    $insertObj['table'],
                    $insertObj['data']
                );
                if ($insertTimeStamp) {
                    $ret = 1;
                }
            }
        }
        return $ret;
    }

    private function parseInsert($sql)
    {
        $matches = array();

        // check string 'spanner'
        if (!preg_match(static::INSERT_REGEX, $sql, $matches)) {
            throw new PDOException(sprintf('Invalid Insert statement %s', $sql));
        }
        //テーブル名取得
        $table = $matches[1];
        //列名取得
        $columns = [];
        $columNum = 0;
        preg_match('/\(\s*([\w\d\W-]+)\s*\)/', $matches[2], $columnsMatch);
        $columnsQuote = preg_split('/,/', $columnsMatch[1]);
        if ($columnsQuote === false) {
            $columns = array(trim(trim($columnsMatch[1], '\'')));
        } else {
            foreach ($columnsQuote as $value) {
                $columNum = array_push($columns, trim(trim($value), '\''));
            }
        }
        //値取得
        $columnValues = [];
        $valueNum = 0;
        preg_match('/\(\s*([\w\d\W-]+)\s*\)/', $matches[3], $valuesMatch);
        $valuesQuote = preg_split('/,/', $valuesMatch[1]);
        if ($valuesQuote === false) {
            $columnValues = array(trim(trim($valuesMatch[1]), '\''));
        } else {
            foreach ($valuesQuote as $value) {
                $valueNum = array_push($columnValues, trim(trim($value), '\''));
            }
        }

        if ($columNum != $valueNum) {
            throw new PDOException(sprintf('Invalid statement column number is not equals values number %s', $sql));
        }

        $dataArray = array();
        for ($i=0; $i<$columNum; $i++) {
            $dataArray[$columns[$i]] = $columnValues[$i];
        }
        return ['table' => $matches[1], 'data' => $dataArray];
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
