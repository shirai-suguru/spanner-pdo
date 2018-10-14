<?php
/**
 * This file is SQL Parser Test
 *
 * @license https://opensource.org/licenses/MIT MIT
 */
namespace SpannerPDO\Sql\Parser;

class SQLParserTest extends \PHPUnit_Framework_TestCase
{
    public function testParseInsert()
    {
        $mock = $this->getMockForTrait(SQLParser::class);
            
        $queryParts = $mock->parseInsert("INSERT INTO test ('testColumn') values (1);");
        $this->assertTrue($queryParts['table'] === "test");
        $this->assertTrue(array_key_exists('testColumn', $queryParts['data']));
        $this->assertTrue($queryParts['data']['testColumn'] == 1);
    }

    public function testParseUpdate()
    {
        $mock = $this->getMockForTrait(SQLParser::class);

        $queryParts = $mock->parseUpdate("Update  test set testColumn = 1 WHERE ID = 1;");
        $this->assertTrue($queryParts['table'] === "test");
        $this->assertTrue(array_key_exists('testColumn', $queryParts['data']));
        $this->assertTrue($queryParts['data']['testColumn'] == 1);
        $this->assertTrue($queryParts['where'] === 'ID = 1');

        $queryParts = $mock->parseUpdate("Update  test2 set testColumn = 1, test2Column = 2 WHERE ID = 1 AND test = 'hoge';");
        $this->assertTrue($queryParts['table'] === "test2");
        $this->assertTrue(array_key_exists('testColumn', $queryParts['data']));
        $this->assertTrue(array_key_exists('test2Column', $queryParts['data']));
        $this->assertTrue($queryParts['data']['testColumn'] == 1);
        $this->assertTrue($queryParts['data']['test2Column'] == 2);
        $this->assertTrue($queryParts['where'] === "ID = 1 AND test = 'hoge'");
    }

    public function testParseSelect()
    {
        $mock = $this->getMockForTrait(SQLParser::class);

        $queryParts = $mock->parseSelect("SELECT testColumn FROM testTable WHERE ID = 1;");
        $this->assertTrue($queryParts['table'] === "testTable");
        $this->assertTrue($queryParts['bindData'] === []);
        $this->assertTrue($queryParts['where'] === 'ID = 1');

        $queryParts = $mock->parseSelect("SElect testColumn,testColumn2 FROM test2 WHERE ID = 1 AND test = 'hoge';");
        $this->assertTrue($queryParts['table'] === "test2");
        $this->assertTrue($queryParts['bindData'] === []);
        $this->assertTrue($queryParts['where'] === "ID = 1 AND test = 'hoge'");
    }
}
