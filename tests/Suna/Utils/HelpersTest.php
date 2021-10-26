<?php declare(strict_types=1);

namespace Suna\Utils;

use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase
{
    public function testFalseToNull(): void
    {
        $this->assertEquals(null, Helpers::falseToNull(false));
        $this->assertEquals(10, Helpers::falseToNull(10));
        $this->assertEquals("TEST", Helpers::falseToNull("TEST"));
    }
}
