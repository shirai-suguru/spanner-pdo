<?php
/**
 * This file is part of Spanner-PDO for PHP.
 *
 * @license https://opensource.org/licenses/MIT MIT
 */
namespace SpannerPDO\Sql;

use Closure;
use Google\Cloud\Spanner\SpannerClient;
use Google\Cloud\Spanner\Instance;

class spannerPdoTest extends \PHPUnit_Framework_TestCase
{
        
    /** @var string instanceId */
    protected static $instanceId;
        
    /** @var string databaseId */
    protected static $databaseId;
        
    /** @var $instance Instance */
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
        }, $this, PDO::class)->__invoke();
    }
}
