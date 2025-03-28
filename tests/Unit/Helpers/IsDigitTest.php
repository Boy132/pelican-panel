<?php

namespace App\Tests\Unit\Helpers;

use App\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class IsDigitTest extends TestCase
{
    /**
     * Test the is_digit helper.
     */
    #[DataProvider('helperDataProvider')]
    public function test_helper($value, $response): void
    {
        $this->assertSame($response, is_digit($value));
    }

    /**
     * Provide data to test against the helper function.
     */
    public static function helperDataProvider(): array
    {
        return [
            [true, false],
            [false, false],
            [12.3, false],
            ['12.3', false],
            ['string', false],
            [-1, false],
            ['-1', false],
            [1, true],
            [0, true],
            [12345, true],
            ['12345', true],
            ['true', false],
            ['false', false],
            ['123_test', false],
            ['123.test', false],
            ['123test', false],
            ['test123', false],
            ['0x00000003', false],
            [00000011, true],
            ['00000011', true],
            ['AD9C', false],
        ];
    }
}
