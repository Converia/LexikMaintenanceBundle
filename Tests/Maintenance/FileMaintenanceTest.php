<?php

namespace Lexik\Bundle\MaintenanceBundle\Tests\Maintenance;

use InvalidArgumentException;
use Lexik\Bundle\MaintenanceBundle\Drivers\FileDriver;
use Lexik\Bundle\MaintenanceBundle\Tests\TestHelper;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\Translation\IdentityTranslator;

/**
 * Test driver file
 *
 * @package LexikMaintenanceBundle
 * @author  Gilles Gauthier <g.gauthier@lexik.fr>
 */
class FileMaintenanceTest extends TestCase
{
    static protected $tmpDir;
    protected $container;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$tmpDir = sys_get_temp_dir().'/symfony2_finder';
    }

    public function setUp(): void
    {
        $this->container = $this->initContainer();
    }

    public function tearDown(): void
    {
        $this->container = null;
    }

    public function testDecide()
    {
        $options = array('file_path' => self::$tmpDir.'/lock.lock');

        $fileM = new FileDriver($options);
        $fileM->setTranslator($this->getTranslator());

        $this->assertTrue($fileM->decide());

        $options = array('file_path' => self::$tmpDir.'/clok');

        $fileM2 = new FileDriver($options);
        $fileM2->setTranslator($this->getTranslator());
        $this->assertFalse($fileM2->decide());
    }

    public function testExceptionInvalidPath()
    {
        $this->expectException(InvalidArgumentException::class);
        $fileM = new FileDriver(array());
        $fileM->setTranslator($this->getTranslator());
    }

    public function testLock()
    {
        $options = array('file_path' => self::$tmpDir.'/lock.lock');

        $fileM = new FileDriver($options);
        $fileM->setTranslator($this->getTranslator());
        $fileM->lock();

        $this->assertFileExists($options['file_path']);
    }

    public function testUnlock()
    {
        $options = array('file_path' => self::$tmpDir.'/lock.lock');

        $fileM = new FileDriver($options);
        $fileM->setTranslator($this->getTranslator());
        $fileM->lock();

        $fileM->unlock();

        $this->assertFileNotExists($options['file_path']);
    }

    public function testIsExists()
    {
        $options = array('file_path' => self::$tmpDir.'/lock.lock', 'ttl' => 3600);

        $fileM = new FileDriver($options);
        $fileM->setTranslator($this->getTranslator());
        $fileM->lock();

        $this->assertTrue($fileM->isEndTime(3600));
    }

    public function testMessages()
    {
        $options = array('file_path' => self::$tmpDir.'/lock.lock', 'ttl' => 3600);

        $fileM = new FileDriver($options);
        $fileM->setTranslator($this->getTranslator());
        $fileM->lock();

        // lock
        $this->assertEquals($fileM->getMessageLock(true), 'lexik_maintenance.success_lock_file');
        $this->assertEquals($fileM->getMessageLock(false), 'lexik_maintenance.not_success_lock');

        // unlock
        $this->assertEquals($fileM->getMessageUnlock(true), 'lexik_maintenance.success_unlock');
        $this->assertEquals($fileM->getMessageUnlock(false), 'lexik_maintenance.not_success_unlock');
    }

    public function testRemainingTime()
    {
        $options = array('file_path' => self::$tmpDir.'/lock.lock', 'ttl' => 3600);

        $fileM = new FileDriver($options);
        $fileM->setTranslator($this->getTranslator());
        $fileM->lock();

        $restTime = $fileM->getRemainingTimeToLive();
        $restTimeInSeconds = $restTime->s + $restTime->i * 60 + $restTime->h * 3600;
        $this->assertInstanceOf(\DateInterval::class,$restTime);
        $this->assertEquals(0,$restTime->invert);

        $this->assertGreaterThan(1,$restTimeInSeconds);
        $this->assertLessThan(3601,$restTimeInSeconds);
    }

    public function testRemainingTimeExpired()
    {
        $options = array('file_path' => self::$tmpDir.'/lock.lock', 'ttl' => 1);

        $fileM = new FileDriver($options);
        $fileM->setTranslator($this->getTranslator());
        $fileM->lock();

        sleep(2);//Am i evil?
        $restTime = $fileM->getRemainingTimeToLive();
        $this->assertNull($restTime);
    }

    public function testRemainingTimeNotSet()
    {
        $options = array('file_path' => self::$tmpDir.'/lock.lock');

        $fileM = new FileDriver($options);
        $fileM->setTranslator($this->getTranslator());
        $fileM->lock();

        $restTime = $fileM->getRemainingTimeToLive();
        $this->assertNull($restTime);
    }

    static public function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
    }

    protected function initContainer()
    {
        $container = new ContainerBuilder(new ParameterBag(array(
            'kernel.debug'          => false,
            'kernel.bundles'        => array('MaintenanceBundle' => 'Lexik\Bundle\MaintenanceBundle'),
            'kernel.cache_dir'      => sys_get_temp_dir(),
            'kernel.environment'    => 'dev',
            'kernel.root_dir'       => __DIR__.'/../../../../', // src dir
            'kernel.default_locale' => 'fr',
        )));

        return $container;
    }

    public function getTranslator()
    {
        /** @var IdentityTranslator $messageSelector */
        $messageSelector = $this->getMockBuilder(IdentityTranslator::class)
            ->disableOriginalConstructor()
            ->getMock();

        return TestHelper::getTranslator($this->container, $messageSelector);
    }
}
