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

    public function testMinMax(): void
    {
        $arrTest = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 999];
        $this->assertEquals(1, Arrays::minMax($arrTest));
        $this->assertEquals(999, Arrays::minMax($arrTest, true));

        // returns the maximum even value of an array
        $this->assertEquals(10, Arrays::minMax($arrTest, true, false, '', function ($el) {
            return(!($el & 1));
        }));

        // returns the minimum even value of an array
        $this->assertEquals(2, Arrays::minMax($arrTest, false, false, '', function ($el) {
            return(!($el & 1));
        }));

        // returns the maximum odd value of an array
        $this->assertEquals(999, Arrays::minMax($arrTest, true, false, '', function ($el) {
            return(($el & 1));
        }));

        // returns the minimum odd value of an array
        $this->assertEquals(1, Arrays::minMax($arrTest, false, false, '', function ($el) {
            return(($el & 1));
        }));

        $arrTest = [
            [
                'id' => 1,
            ], [
                'id' => 3,
            ], [
                'id' => 4,
            ], [
                'id' => 5,
            ], [
                'id' => 7,
            ]
        ];
        $this->assertEquals(1, Arrays::minMax($arrTest, false, true, 'id'));
        $this->assertEquals(7, Arrays::minMax($arrTest, true, true, 'id'));

        // returns the maximum even value of an array
        $this->assertEquals(4, Arrays::minMax($arrTest, true, true, 'id', function ($el) {
            return(!($el['id'] & 1));
        }));

        // returns the minimum even value of an array
        $this->assertEquals(4, Arrays::minMax($arrTest, false, true, 'id', function ($el) {
            return(!($el['id'] & 1));
        }));

        // returns the maximum odd value of an array
        $this->assertEquals(7, Arrays::minMax($arrTest, true, true, 'id', function ($el) {
            return(($el['id'] & 1));
        }));

        // returns the minimum odd value of an array
        $this->assertEquals(1, Arrays::minMax($arrTest, false, true, 'id', function ($el) {
            return(($el['id'] & 1));
        }));

        // Min Empty Array
        $this->assertEquals(0, Arrays::minMax($arrTest, false, true, 'id', function ($el) {
            return(($el['id'] > 10));
        }));

        // Max Empty Array
        $this->assertEquals(0, Arrays::minMax($arrTest, false, true, 'id', function ($el) {
            return(($el['id'] > 10));
        }));
    }

}
