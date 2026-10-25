<?php

declare(strict_types=1);

namespace Teqnomaze\Coverage\Test;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Teqnomaze\Coverage\Inspector;

/**
 * The Inspector test class.
 */
class InspectorTest extends TestCase
{
    /**
     * Get the path of a fixture file.
     *
     * @param string $name The fixture file name.
     *
     * @return string
     */
    private function fixture(string $name): string
    {
        return __DIR__ . '/clovers/' . $name;
    }

    /**
     * Run test for the clover and threshold accessors.
     *
     * @return void
     */
    public function testAccessors(): void
    {
        $object = new Inspector('first.xml');

        $this->assertSame('first.xml', $object->getClover());
        $this->assertSame($object, $object->setClover('second.xml'));
        $this->assertSame('second.xml', $object->getClover());

        $this->assertSame(80, $object->getThreshold());
        $this->assertSame($object, $object->setThreshold(90));
        $this->assertSame(90, $object->getThreshold());
    }

    /**
     * Run test for the state before the check has run.
     *
     * @return void
     */
    public function testStateBeforeCheck(): void
    {
        $object = new Inspector($this->fixture('clover-test.xml'));

        $this->assertNull($object->getCoverage());
        $this->assertNull($object->getMessage());
        $this->assertFalse($object->hasPassed());
        $this->assertSame(1, $object->getExitCode());
    }

    /**
     * Run test for thresholds outside the 1 to 100 range.
     *
     * @return void
     */
    public function testThresholdOutOfRange(): void
    {
        foreach ([-1, 0, 101] as $threshold) {
            try {
                new Inspector($this->fixture('clover-test.xml'), $threshold);
                $this->fail(sprintf('Threshold %d should have been rejected.', $threshold));
            } catch (InvalidArgumentException $exception) {
                $this->assertSame('Threshold must be between 1 and 100.', $exception->getMessage());
            }
        }

        $object = new Inspector($this->fixture('clover-test.xml'));
        $this->expectException(InvalidArgumentException::class);
        $object->setThreshold(101);
    }

    /**
     * Run test for the boundary thresholds.
     *
     * @return void
     */
    public function testThresholdBoundaries(): void
    {
        $this->assertSame(1, (new Inspector('x.xml', 1))->getThreshold());
        $this->assertSame(100, (new Inspector('x.xml', 100))->getThreshold());
    }

    /**
     * Run test for `check` with a missing file.
     *
     * @return void
     */
    public function testCheckWithMissingFile(): void
    {
        $object = new Inspector('./tests/missing.xml');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Clover file not found: ./tests/missing.xml');

        $object->check();
    }

    /**
     * Run test for `check` with an invalid XML file.
     *
     * @return void
     */
    public function testCheckWithInvalidXml(): void
    {
        $object = new Inspector($this->fixture('clover-invalid.xml'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid clover file');

        $object->check();
    }

    /**
     * Run test for `check` with a file that has no project metrics.
     *
     * @return void
     */
    public function testCheckWithoutProjectMetrics(): void
    {
        $object = new Inspector($this->fixture('clover-empty.xml'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No project metrics found');

        $object->check();
    }

    /**
     * Run test for `check` with a passing result.
     *
     * @return void
     */
    public function testCheckPassed(): void
    {
        $object = new Inspector($this->fixture('clover-test.xml'));
        $message = 'Code coverage is 88%, which is above the accepted 80%';

        $this->assertSame($object, $object->check());
        $this->assertSame($message, $object->getMessage());
        $this->assertEqualsWithDelta(88.0, $object->getCoverage(), 0.001);
        $this->assertTrue($object->hasPassed());
        $this->assertSame(0, $object->getExitCode());
    }

    /**
     * Run test for `check` with a failing result.
     *
     * @return void
     */
    public function testCheckFailed(): void
    {
        $object = new Inspector($this->fixture('clover-test.xml'), 90);
        $message = 'Code coverage is 88%, which is below the accepted 90%';

        $this->assertSame($object, $object->check());
        $this->assertSame($message, $object->getMessage());
        $this->assertFalse($object->hasPassed());
        $this->assertSame(1, $object->getExitCode());
    }

    /**
     * Run test for coverage that exactly meets the threshold.
     *
     * @return void
     */
    public function testCheckPassesAtExactThreshold(): void
    {
        $object = (new Inspector($this->fixture('clover-test.xml'), 88))->check();

        $this->assertTrue($object->hasPassed());
    }

    /**
     * Run test to make sure coverage is not rounded up to pass.
     *
     * @return void
     */
    public function testCheckDoesNotRoundUp(): void
    {
        $object = (new Inspector($this->fixture('clover-rounding.xml'), 80))->check();

        $this->assertEqualsWithDelta(79.6, $object->getCoverage(), 0.001);
        $this->assertFalse($object->hasPassed());
    }

    /**
     * Run test for a report that has no elements.
     *
     * @return void
     */
    public function testCheckWithZeroElements(): void
    {
        $object = (new Inspector($this->fixture('clover-zero.xml')))->check();

        $this->assertEqualsWithDelta(0.0, $object->getCoverage(), 0.001);
        $this->assertFalse($object->hasPassed());
    }

    /**
     * Run test for the `output` function with a passing result in color.
     *
     * @return void
     */
    public function testOutputPassedWithColor(): void
    {
        $object = (new Inspector($this->fixture('clover-test.xml')))->check();

        ob_start();
        $object->output(true);
        $output = (string) ob_get_clean();

        $this->assertStringContainsString("\033[42;37m", $output);
        $this->assertStringContainsString("\033[0m", $output);
        $this->assertStringContainsString('which is above the accepted 80%', $output);
    }

    /**
     * Run test for the `output` function with a failing result in color.
     *
     * @return void
     */
    public function testOutputFailedWithColor(): void
    {
        $object = (new Inspector($this->fixture('clover-test.xml'), 90))->check();

        ob_start();
        $object->output(true);
        $output = (string) ob_get_clean();

        $this->assertStringContainsString("\033[41;37m", $output);
        $this->assertStringContainsString("\033[0m", $output);
        $this->assertStringContainsString('which is below the accepted 90%', $output);
    }

    /**
     * Run test for the `output` function without color.
     *
     * @return void
     */
    public function testOutputWithoutColor(): void
    {
        $object = (new Inspector($this->fixture('clover-test.xml')))->check();

        ob_start();
        $object->output(false);
        $output = (string) ob_get_clean();

        $this->assertStringNotContainsString("\033", $output);
        $this->assertStringContainsString('which is above the accepted 80%', $output);
    }

    /**
     * Run test to make sure `output` runs the check when it has not run yet.
     *
     * @return void
     */
    public function testOutputRunsCheckWhenNeeded(): void
    {
        $object = new Inspector($this->fixture('clover-test.xml'));

        ob_start();
        $object->output();
        $output = (string) ob_get_clean();

        $this->assertStringContainsString('Code coverage is 88%', $output);
        $this->assertTrue($object->hasPassed());
    }
}
