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

class spannerPdoTest extends \PHPUnit_Framework_TestCase
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
            $dsnParts = $pdo->_parseDSN($testDsnString);
            $this->assertEquals($testInstanceid, $dsnParts['instanceId']);
            $this->assertEquals($testDatabaseId, $dsnParts['databaseId']);

            $this->expectException(PDOException::class);
            $testDsnString = 'SPANNER:instance=' . $testInstanceid . ';dbname=' . $testDatabaseId;
            $dsnParts = $pdo->_parseDSN($testDsnString);
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
    
    public static function tearDownAfterClass()
    {
        if (self::$instance && !getenv('GOOGLE_SPANNER_KEEP_INSTANCE')) {
            self::$instance->delete();
        }
    }
}
