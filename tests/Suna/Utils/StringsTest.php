<?php declare(strict_types=1);

namespace Suna\Utils;

use PHPUnit\Framework\TestCase;

class StringsTest extends TestCase
{
    public function testMask(): void
    {
        $this->assertEquals('+00 (11) 23333-4444', Strings::mask('0011233334444', '+## (##) #####-####'));
        $this->assertEquals('012.345.678-99', Strings::mask('01234567899', '###.###.###-##'));
    }
}
