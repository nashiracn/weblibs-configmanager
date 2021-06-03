<?php

namespace clagiordano\weblibs\configmanager\tests;

use clagiordano\weblibs\configmanager\IConfigurable;
use PHPUnit\Framework\TestCase;

/**
 * Class AbstractConfigManagerTest
 * @package clagiordano\weblibs\configmanager\tests
 */
abstract class AbstractConfigManagerTest extends TestCase
{
    /** @var string $configFile  */
    protected $configFile = null;
    /** @var IConfigurable $config */
    protected $config = null;

    public function testBasicUsage()
    {
        $this->assertNotNull(
            $this->config->getValue('app')
        );
    }

    public function testFastUsage()
    {
        $this->assertNotNull(
            $this->config->getValue('app')
        );
    }

    public function testFastInvalidKey()
    {
        $this->assertNull(
            $this->config->getValue('invalidKey')
        );
    }

    public function testFastInvalidKeyWithDefault()
    {
        $this->assertEquals(
            $this->config->getValue('invalidKey', 'defaultValue'),
            'defaultValue'
        );
    }

    public function testFastNestedConfig()
    {
        $this->assertNotNull(
            $this->config->getValue('other.multi.deep.nested')
        );
    }

    public function testCheckExistConfig()
    {
        $this->assertTrue(
            $this->config->existValue('other.multi.deep.nested')
        );
    }

    public function testCheckNotExistConfig()
    {
        $this->assertFalse(
            $this->config->existValue('invalid.config.path')
        );
    }

    public function testSetValue()
    {
        $this->config->setValue('other.multi.deep.nested', __FUNCTION__);

        $this->assertEquals(
            $this->config->getValue('other.multi.deep.nested'),
            __FUNCTION__
        );
    }

    public function testFailedSaveConfig()
    {
        $this->setExpectedException('Exception');
        $this->config->saveConfigFile('/invalid/path');
    }

    public function testSuccessSaveConfigOnTempAndReload()
    {
        $this->config->setValue('other.multi.deep.nested', "SUPERNESTED");
        $this->config->saveConfigFile("/tmp/testconfig.sample", true);

        $this->assertEquals(
            $this->config->getValue('other.multi.deep.nested'),
            "SUPERNESTED"
        );
    }

    public function testOverwriteSameConfigFile()
    {
        $this->config->saveConfigFile();
    }

    public function testFailWriteConfig()
    {
        $this->setExpectedException('\RuntimeException');
        $this->config->saveConfigFile('/invalid/path/test.sample');
    }

    /**
     * @test
     * @group permissions
     */
    public function canRaiseExceptionOnUnreadableFile()
    {
        /**
         * Create new temp file
         */
        $testFile = tempnam('/tmp', 'phpunit');
        self::assertFileExists($testFile);

        /**
         * Make tempfile unreadable by everyone, but still writeable
         */
        $status = chmod($testFile, 0200);
        self::assertTrue($status);

        /**
         * Check permissions it must be 0200 ( --w------- )
         */
        $filePerms = (fileperms($testFile) & 0777);
        self::assertSame(0200, $filePerms);

        /**
         * Try to read that file, an exception must be thrown
         */
        self::setExpectedException('\RuntimeException');
        $this->config->loadConfig($testFile);

        /**
         * Remove temp file
         */
        unlink($testFile);
    }

    /**
     * @return array
     */
    public function configDataProvider()
    {
        return [
            [
                __DIR__ . '/../testsdata/sample_config_data.converted.yml',
                '\clagiordano\weblibs\configmanager\YamlConfigManager',
            ],
            [
                __DIR__ . '/../testsdata/sample_config_data.converted.json',
                '\clagiordano\weblibs\configmanager\JsonConfigManager',
            ],
            [
                __DIR__ . '/../testsdata/sample_config_data.converted.php',
                '\clagiordano\weblibs\configmanager\ArrayConfigManager',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider configDataProvider
     * @param mixed $targetConfig
     * @param mixed $targetInstance
     */
    public function canConvertOneFormatToAnother($targetConfig, $targetInstance)
    {
        if (file_exists($targetConfig)) {
            /**
             * Drop target file if already existing
             */
            unlink($targetConfig);
        }

        self::assertFileNotExists($targetConfig);

        $target = new $targetInstance($targetConfig);
        self::assertInstanceOf($targetInstance, $target);

        $converted = $this->config->convert($target);
        self::assertInstanceOf($targetInstance, $converted);

        self::assertFileNotExists($targetConfig);
    }
}