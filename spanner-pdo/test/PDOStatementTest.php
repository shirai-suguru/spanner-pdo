<?php
/**
 * This file is part of Spanner-PDO for PHP.
 *
 * @license https://opensource.org/licenses/MIT MIT
 */
namespace SpannerPDO\Sql;

use Google\Cloud\Spanner\SpannerClient;
use Google\Cloud\Spanner\Instance;
use PDO as BasePDO;

class PDOStatementTest extends \PHPUnit_Framework_TestCase
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
        
        $dsnString = 'spanner:instance=' . self::$instanceId . ';dbname=' . self::$databaseId;
        $pdo = new PDO($dsnString, "", "");
        
        $ret = $pdo->exec('CREATE TABLE Singers (
            SingerId     INT64 NOT NULL,
            FirstName    STRING(1024),
            LastName     STRING(1024),
            SingerInfo   BYTES(MAX)
        ) PRIMARY KEY (SingerId)');

        $ret = $pdo->exec('CREATE TABLE Albums (
            SingerId     INT64 NOT NULL,
            AlbumId      INT64 NOT NULL,
            AlbumTitle   STRING(MAX)
        ) PRIMARY KEY (SingerId, AlbumId),
        INTERLEAVE IN PARENT Singers ON DELETE CASCADE');
    }

    private function getPDO()
    {
        $dsnString = 'spanner:instance=' . self::$instanceId . ';dbname=' . self::$databaseId;
        return new PDO($dsnString, "", "");
    }

    /**
     * @test
     */
    public function testExecute()
    {
         $pdo = $this->getPDO();
         $sth = $pdo->prepare("SELECT * FROM Singers");
         $ret = $sth->execute();
         $this->assertTrue($ret);

         $sth = $pdo->prepare("SELECT * FROM Singers WHERE SingerId = @singerId");
         $ret = $sth->execute();
         $this->assertFalse($ret);

         $sth = $pdo->prepare("SELECT * FROM Singers WHERE SingerId = @singerId");
         $ret = $sth->execute(['@singerid' => 1]);
         $this->assertTrue($ret);
    }
     

    public static function tearDownAfterClass()
    {
        if (self::$instance && !getenv('GOOGLE_SPANNER_KEEP_INSTANCE')) {
            self::$instance->delete();
        }
    }
    
}
