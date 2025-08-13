<?php
/**
 * The CodeCoverage class file.
 *
 * @package Teqnomaze\Coverage\Test
 * @author  Musthafa SM <musthafasm@gmail.com>
 */

declare(strict_types=1);

namespace Teqnomaze\Coverage\Test;

use Teqnomaze\Coverage\CodeCoverage;

/**
 * The CodeCoverage class.
 */
class CodeCoverageTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Run test for private properties.
     *
     * @return void
     */
    public function testProperties(): void
    {
        $object = new CodeCoverage();

        $clover = './tests/clover-test.xml';
        $this->assertEmpty($object->getClover());
        $this->assertInstanceOf(CodeCoverage::class, $object->setClover($clover));
        $this->assertEquals($clover, $object->getClover());

        $threshold = 90;
        $this->assertEquals(80, $object->getThreshold());
        $this->assertInstanceOf(CodeCoverage::class, $object->setThreshold($threshold));
        $this->assertEquals($threshold, $object->getThreshold());

        $message = 'Passed!';
        $this->assertNull($object->getMessage());
        $this->assertInstanceOf(CodeCoverage::class, $object->setMessage($message));
        $this->assertEquals($message, $object->getMessage());

        $passed = true;
        $this->assertFalse($object->getPassed());
        $this->assertInstanceOf(CodeCoverage::class, $object->setPassed($passed));
        $this->assertEquals($passed, $object->getPassed());
    }

    /**
     * Run unit testing for `check` function and capture the Exception.
     *
     * @return void
     */
    public function testCheckWithException(): void
    {
        $object = new CodeCoverage();

        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Clover file not found!');

        $object->check();
    }

    /**
     * Run unit testing for `check` function with a success message.
     *
     * @return void
     */
    public function testCheckPassed(): void
    {
        $object = new CodeCoverage('./tests/clover-test.xml');
        $message = 'Code coverage is 88%, which is above the accepted 80%';

        $this->assertInstanceOf(CodeCoverage::class, $object->check());
        $this->assertEquals($message, $object->getMessage());
        $this->assertTrue($object->getPassed());
    }

    /**
     * Run unit testing for `check` function with a failed message.
     *
     * @return void
     */
    public function testCheckFailed(): void
    {
        $object = new CodeCoverage('./tests/clover-test.xml', 90);
        $message = 'Code coverage is 88%, which is below the accepted 90%';

        $this->assertInstanceOf(CodeCoverage::class, $object->check());
        $this->assertEquals($message, $object->getMessage());
        $this->assertFalse($object->getPassed());
    }

    /**
     * Run unit testing for `output` function with a success message.
     *
     * @return void
     */
    public function testOutputPassed(): void
    {
        $object = (new CodeCoverage('./tests/clover-test.xml'))->check();

        ob_start();
        $object->output();
        $output = ob_get_clean();

        $this->assertStringContainsString('which is above the accepted 80%', $output);
    }

    /**
     * Run unit testing for `output` function with a failed message.
     *
     * @return void
     */
    public function testOutputFailed(): void
    {
        $object = (new CodeCoverage('./tests/clover-test.xml', 90))->check();

        ob_start();
        $object->output();
        $output = ob_get_clean();

        $this->assertStringContainsString('which is below the accepted 90%', $output);
    }
}
