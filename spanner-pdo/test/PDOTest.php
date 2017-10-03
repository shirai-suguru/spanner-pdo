<?php
/**
 * This file is part of Spanner-PDO for PHP.
 *
 * @license https://opensource.org/licenses/MIT MIT
 */
namespace SpannerPDO\Sql;

use Closure;
use SpannerPDO\Sql\Exception\PDOException;
use Google\Cloud\Spanner\SpannerClient;
use Google\Cloud\Spanner\Transaction;
use Google\Cloud\Spanner\Instance;
use Google\Cloud\Core\Exception\NotFoundException;

class SpannerPdoTest extends \PHPUnit_Framework_TestCase
{
        
    /** @var string instanceId */
    protected static $instanceId;
        
    /** @var string databaseId */
    protected static $databaseId;
        
    /** @var Instance Instance */
    protected static $instance;

    public static function setUpBeforeClass()
    {
        if (!getenv('GOOGLE_APPLICATION_CREDENTIALS')) {
            self::markTestSkipped('No application credentials were found');
        }
        if (!$projectId = getenv('GOOGLE_PROJECT_ID')) {
            self::markTestSkipped('GOOGLE_PROJECT_ID must be set.');
        }
        $spanner = new SpannerClient([
            'projectId' => $projectId,
        ]);

        self::$instanceId = self::$databaseId = 'test-' . time() . rand();
        $configurationId = "projects/$projectId/instanceConfigs/regional-us-central1";
        
        $configuration = $spanner->instanceConfiguration($configurationId);
        $instance = $spanner->instance(self::$instanceId);
        
        $operation = $instance->create($configuration);
        $operation->pollUntilComplete();

        self::$instance = $instance;

        $operation = $instance->createDatabase(self::$databaseId);
        $operation->pollUntilComplete();
    }


    public function testParseDSN()
    {
        $dsnString = 'spanner:instance=' . self::$instanceId . ';dbname=' . self::$databaseId;
        Closure::bind(function () use ($dsnString) {
            $pdo = new PDO($dsnString, "", "");

            $testInstanceid = "test-instance";
            $testDatabaseId = "test-database";
            $testDsnString = 'spanner:instance=' . $testInstanceid . ';dbname=' . $testDatabaseId;
            $dsnParts = $pdo->parseDSN($testDsnString);
            $this->assertEquals($testInstanceid, $dsnParts['instanceId']);
            $this->assertEquals($testDatabaseId, $dsnParts['databaseId']);

            $this->expectException(PDOException::class);
            $testDsnString = 'SPANNER:instance=' . $testInstanceid . ';dbname=' . $testDatabaseId;
            $dsnParts = $pdo->parseDSN($testDsnString);
        }, $this, PDO::class)->__invoke();
    }

    public function testBeginTransaction()
    {
        $dsnString = 'spanner:instance=' . self::$instanceId . ';dbname=' . self::$databaseId;
        $pdo = new PDO($dsnString, "", "");
        $ret = $pdo->beginTransaction();
        if ($ret === true) {
            $this->assertTrue($pdo->inTransaction());
        }
    }
    /**
     * InTransaction test.
     *
     * @return void
     */
    public function testInTransaction()
    {
        $dsnString = 'spanner:instance=' . self::$instanceId . ';dbname=' . self::$databaseId;
        $pdo = new PDO($dsnString, "", "");
        $this->assertFalse($pdo->inTransaction());
    }

    public function testCommit()
    {

        $dsnString = 'spanner:instance=' . self::$instanceId . ';dbname=' . self::$databaseId;
        Closure::bind(function () use ($dsnString) {
            $pdo = new PDO($dsnString, "", "");
            $this->assertFalse($pdo->commit());

            $pdo->beginTransaction();
            $this->assertTrue($pdo->commit());
            $this->assertTrue($pdo->transaction->state() === Transaction::STATE_COMMITTED);

            //２回目のcommitはfalseをかえす
            $this->assertFalse($pdo->commit());
        }, $this, PDO::class)->__invoke();
    }

    public function testRollBack()
    {
        $dsnString = 'spanner:instance=' . self::$instanceId . ';dbname=' . self::$databaseId;
        Closure::bind(function () use ($dsnString) {
            $pdo = new PDO($dsnString, "", "");
            $this->assertFalse($pdo->rollback());

            $pdo->beginTransaction();
            $this->assertTrue($pdo->rollback());
            $this->assertTrue($pdo->transaction->state() === Transaction::STATE_ROLLED_BACK);

            //２回目のrollbackはfalseをかえす
            $this->assertFalse($pdo->rollback());
        }, $this, PDO::class)->__invoke();
    }

    public function testParseInsert()
    {
        $dsnString = 'spanner:instance=' . self::$instanceId . ';dbname=' . self::$databaseId;
        Closure::bind(function () use ($dsnString) {
            $pdo = new PDO($dsnString, "", "");

            $queryParts = $pdo->parseInsert("INSERT INTO test ('testColumn') values (1);");
            $this->assertTrue($queryParts['table'] === "test");
            $this->assertTrue(array_key_exists('testColumn', $queryParts['data']));
            $this->assertTrue($queryParts['data']['testColumn'] == 1);
        }, $this, PDO::class)->__invoke();
    }

    public function testParseUpdate()
    {
        $dsnString = 'spanner:instance=' . self::$instanceId . ';dbname=' . self::$databaseId;
        Closure::bind(function () use ($dsnString) {
            $pdo = new PDO($dsnString, "", "");

            $queryParts = $pdo->parseUpdate("Update  test set testColumn = 1 WHERE ID = 1;");
            $this->assertTrue($queryParts['table'] === "test");
            $this->assertTrue(array_key_exists('testColumn', $queryParts['data']));
            $this->assertTrue($queryParts['data']['testColumn'] == 1);
            $this->assertTrue($queryParts['where'] === 'ID = 1');

            $queryParts = $pdo->parseUpdate("Update  test2 set testColumn = 1, test2Column = 2 WHERE ID = 1 AND test = 'hoge';");
            $this->assertTrue($queryParts['table'] === "test2");
            $this->assertTrue(array_key_exists('testColumn', $queryParts['data']));
            $this->assertTrue(array_key_exists('test2Column', $queryParts['data']));
            $this->assertTrue($queryParts['data']['testColumn'] == 1);
            $this->assertTrue($queryParts['data']['test2Column'] == 2);
            $this->assertTrue($queryParts['where'] === "ID = 1 AND test = 'hoge'");
        }, $this, PDO::class)->__invoke();
    }


    public function testExecCreate()
    {
        $dsnString = 'spanner:instance=' . self::$instanceId . ';dbname=' . self::$databaseId;
        $pdo = new PDO($dsnString, "", "");

        //Create statement
        $ret = $pdo->exec('CREATE TABLE Singers (
            SingerId     INT64 NOT NULL,
            FirstName    STRING(1024),
            LastName     STRING(1024),
            SingerInfo   BYTES(MAX)
        ) PRIMARY KEY (SingerId)');
        $this->assertEquals($ret, 1);

        $ret = $pdo->exec('CREATE TABLE Albums (
            SingerId     INT64 NOT NULL,
            AlbumId      INT64 NOT NULL,
            AlbumTitle   STRING(MAX)
        ) PRIMARY KEY (SingerId, AlbumId),
        INTERLEAVE IN PARENT Singers ON DELETE CASCADE');
        $this->assertEquals($ret, 1);
    }

    /**
     * @test
     * @depends testExecCreate
     */
    public function testGetPKeyFromTable()
    {
        $dsnString = 'spanner:instance=' . self::$instanceId . ';dbname=' . self::$databaseId;
        Closure::bind(function () use ($dsnString) {
            $pdo = new PDO($dsnString, "", "");
            $tablePK = $pdo->getPKeyFromTable('Singers');
            $this->assertTrue(in_array('SingerId', $tablePK, true));
 
            $tablePK = $pdo->getPKeyFromTable('Albums');
            $this->assertTrue(in_array('SingerId', $tablePK, true));
            $this->assertTrue(in_array('AlbumId', $tablePK, true));
        }, $this, PDO::class)->__invoke();
    }
 
    /**
     * @test
     * @depends testExecCreate
     */
    public function testExecInsert()
    {
        $dsnString = 'spanner:instance=' . self::$instanceId . ';dbname=' . self::$databaseId;
        $pdo = new PDO($dsnString, "", "");
    
        //Insert statement
        $retInt = $pdo->exec("INSERT INTO Singers ('SingerId', 'FirstName') values (1, 'hogefuga');");
        $this->assertEquals($retInt, 1);

        $retInt = $pdo->exec("INSERT INTO Singers ('SingerId') values (2);");
        $this->assertEquals($retInt, 1);

        $retInt = $pdo->exec("INSERT INTO Albums ('SingerId', 'AlbumId', 'AlbumTitle') values (1, 1, 'Total Junk');");
        $this->assertEquals($retInt, 1);
    
        $retInt = $pdo->exec("INSERT INTO Albums ('SingerId', 'AlbumId', 'AlbumTitle') values (1, 2, 'Junk Food');");
        $this->assertEquals($retInt, 1);

        
        $this->expectException(PDOException::class);
        $retInt = $pdo->exec("INSERT  Singers ('test') values (1);");
 
        $this->expectException(PDOException::class);
        $retInt = $pdo->exec("INSERT  INTO Singers ('SingerId') values (1,2);");

        $this->expectException(NotFoundException::class);
        $retInt = $pdo->exec("INSERT INTO test ('test') values (1);");
    }

    /**
     * @test
     * @depends testExecInsert
     */
    public function testExecUpdate()
    {
        $dsnString = 'spanner:instance=' . self::$instanceId . ';dbname=' . self::$databaseId;
        $pdo = new PDO($dsnString, "", "");

        //Update statement
        $retInt = $pdo->exec("Update Singers set FirstName = 'hoge' WHERE SingerId = 1;");
        $this->assertEquals($retInt, 1);

        //Update statement
        $retInt = $pdo->exec("Update Albums set AlbumTitle = 'hoge' WHERE SingerId = 1 AND AlbumId = 1;");
        $this->assertEquals($retInt, 1);

        //Update statement
        $retInt = $pdo->exec("Update Albums set AlbumTitle = 'fuga' WHERE SingerId = 1;");
        $this->assertEquals($retInt, 2);
    }
    
    public static function tearDownAfterClass()
    {
        if (self::$instance && !getenv('GOOGLE_SPANNER_KEEP_INSTANCE')) {
            self::$instance->delete();
        }
    }
}
