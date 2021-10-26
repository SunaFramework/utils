<?php declare(strict_types=1);

namespace Suna\Utils;

use PHPUnit\Framework\TestCase;

class ArraysTest extends TestCase
{

    public function testContains(): void
    {
        $arrTest = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 999];
        $this->assertEquals(true, Arrays::contains($arrTest, 10));
        $this->assertEquals(false, Arrays::contains($arrTest, 11));
        $this->assertEquals(false, Arrays::contains($arrTest, 'Test'));

        $arrTest = [
            "test" => 1,
            "test2" => 1,
        ];
        $this->assertEquals(true, Arrays::contains($arrTest, 1));
        $this->assertEquals(false, Arrays::contains($arrTest, 'test'));
    }

    public function testFirst(): void
    {
        $arrTest = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 999];
        $arrTestCopy = $arrTest;
        $this->assertEquals(1, Arrays::first($arrTest));
        // Check that the original array has not changed
        $this->assertEquals($arrTestCopy, $arrTest);

        $arrTest = [
            "test" => 1,
            "test2" => 1,
        ];
        $arrTestCopy = $arrTest;
        $this->assertEquals(1, Arrays::first($arrTest));
        // Check that the original array has not changed
        $this->assertEquals($arrTestCopy, $arrTest);

        $arrTest = [];
        $arrTestCopy = $arrTest;
        $this->assertEquals(null, Arrays::first($arrTest));
        // Check that the original array has not changed
        $this->assertEquals($arrTestCopy, $arrTest);
    }

    public function testLast(): void
    {
        $arrTest = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 999];
        $arrTestCopy = $arrTest;
        $this->assertEquals(999, Arrays::last($arrTest));
        // Check that the original array has not changed
        $this->assertEquals($arrTestCopy, $arrTest);

        $arrTest = [
            "test" => 1,
            "test2" => 10,
        ];
        $arrTestCopy = $arrTest;
        $this->assertEquals(10, Arrays::last($arrTest));
        // Check that the original array has not changed
        $this->assertEquals($arrTestCopy, $arrTest);

        $arrTest = [];
        $arrTestCopy = $arrTest;
        $this->assertEquals(null, Arrays::last($arrTest));
        // Check that the original array has not changed
        $this->assertEquals($arrTestCopy, $arrTest);
    }

}
