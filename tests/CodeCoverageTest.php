<?php
/**
 * The CodeCoverage test class file.
 *
 * @package    Coverage
 * @subpackage Test
 */

namespace Teqnomaze\Coverage\Test;

use \Teqnomaze\Coverage\CodeCoverage;

/**
 * The CodeCoverage class.
 */
class CodeCoverageTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Run unit testing for `check` function with a success message.
     *
     * @return void
     */
    public function testCheckSuccess(): void
    {
        $object  = new CodeCoverage('./tests/clover-test.xml', 85);

        $this->assertInstanceOf(CodeCoverage::class, $object->check());
        $this->assertStringContainsString('which is above the accepted', $object->output(false));
    }

    /**
     * Run unit testing for `check` function witha failed message.
     *
     * @return void
     */
    public function testCheckFalied(): void
    {
        $object  = new CodeCoverage('./tests/clover-test.xml', 90);

        $this->assertInstanceOf(CodeCoverage::class, $object->check());
        $this->assertStringContainsString('which is below the accepted', $object->output(false));
    }

    /**
     * Run unit testing for `check` function and capture the Exception.
     *
     * @return void
     */
    public function testCheckWithException(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('The clover.xml file not provided or not found!');

        (new CodeCoverage('', 90))->check();
    }
}
