<?php

namespace Unicodeveloper\Paystack\Test;

use Mockery as m;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase {

    protected $paystack;

    protected $mock;

    public function setUp(): void
    {
        $this->paystack = m::mock('Unicodeveloper\Paystack\Paystack');
        $this->mock = m::mock('GuzzleHttp\Client');
    }

    public function tearDown(): void
    {
        m::close();
    }

    /**
     * Tests that helper returns
     *
     * @return void
     */
    #[Test]
    function it_returns_instance_of_paystack () {

        $this->assertInstanceOf("Unicodeveloper\Paystack\Paystack", $this->paystack);
    }
}