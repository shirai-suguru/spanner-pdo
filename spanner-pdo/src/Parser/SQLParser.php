<?php
/**
 * This file is SQL Parser
 *
 * @license https://opensource.org/licenses/MIT MIT
 */
namespace SpannerPDO\Sql\Parser;

use SpannerPDO\Sql\Exception\PDOException;

trait SQLParser
{
    private $INSERT_REGEX = '/^[iI][nN][sS][eE][rR][tT]\s+[iI][nN][tT][oO]\s+([\w\d-]+)\s*([\w\d\W]*)[Vv][Aa][Ll][Uu][Ee][Ss]\s*([\w\d\W]*)/';
    private $INSERT_VALUE_REGEX = '/\(\s*([\w\d\W-]+)\s*\)/';
    private $UPDATE_REGEX = '/^[Uu][Pp][Dd][Aa][Tt][Ee]\s+([\w\d\W-]+)\s+[Ss][Ee][Tt]\s+([\w\d\W-]+)[Ww][Hh][Ee][Rr][Ee]\s([\w\d\W-]+)/';
    private $SELECT_REGEX = '/^[Ss][Ee][Ll][Ee][Cc][Tt]\s+([\w\d\W-]+)\s[Ff][Rr][Oo][Mm]\s+([\w\d\W-]+)\s+[Ww][Hh][Ee][Rr][Ee]\s+([\w\d\W-]+)/';

    public function parseInsert($sql)
    {
        $matches = array();
        
        // check string 'insert'
        if (!preg_match($this->INSERT_REGEX, $sql, $matches)) {
            throw new PDOException(sprintf('Invalid Insert statement %s', $sql));
        }
        //テーブル名取得
        $table = $matches[1];
        //列名取得
        $columns = [];
        $columNum = 0;
        preg_match($this->INSERT_VALUE_REGEX, $matches[2], $columnsMatch);
        $columnsQuote = preg_split('/,/', $columnsMatch[1]);
        foreach ($columnsQuote as $value) {
            $columNum = array_push($columns, trim(trim($value), '\''));
        }
        
        //値取得
        $columnValues = [];
        $valueNum = 0;
        preg_match($this->INSERT_VALUE_REGEX, $matches[3], $valuesMatch);
        $valuesQuote = preg_split('/,/', $valuesMatch[1]);
        foreach ($valuesQuote as $value) {
            $valueNum = array_push($columnValues, trim(trim($value), '\''));
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

    public function parseUpdate($sql)
    {
        $matches = array();
        // check string 'update' TODO WHEREが必須なのを直す
        if (!preg_match($this->UPDATE_REGEX, trim(trim($sql), ';'), $matches)) {
            throw new PDOException(sprintf('Invalid Update statement %s', $sql));
        }
        //テーブル名取得
        $table = $matches[1];
        //Where句取得
        $where = $matches[3];

        //更新する値取得
        $upColumnValues = [];
        $upValues = [];
        $upColumnNum = 0;
        $upValueNum = 0;
        $upValuesQuote = preg_split('/,/', trim($matches[2]));

        foreach ($upValuesQuote as $setValue) {
            $setValueQuote = preg_split('/=/', trim($setValue));
            $upColumnNum = array_push($upColumnValues, trim($setValueQuote[0]));
            $upValueNum = array_push($upValues, trim(trim($setValueQuote[1]), '\''));
        }

        if ($upColumnNum != $upValueNum) {
            throw new PDOException(sprintf('Invalid statement column number is not equals values number %s', $sql));
        }

        for ($i=0; $i<$upColumnNum; $i++) {
            $dataArray[$upColumnValues[$i]] = $upValues[$i];
        }
        
        return ['table' => $table, 'data' => $dataArray, 'where' => trim($where)];
    }

    public function parseSelect($sql)
    {
        $matches = array();
        // check string 'update' TODO WHEREが必須なのを直す
        if (!preg_match($this->SELECT_REGEX, trim(trim($sql), ';'), $matches)) {
            throw new PDOException(sprintf('Invalid SELECT statement %s', $sql));
        }
        //テーブル名取得
        $table = $matches[2];
        //Where句取得
        $where = $matches[3];

        $bindArray = [];
        
        return ['table' => $table, 'bindData' => $bindArray, 'where' => trim($where)];
    }
}
