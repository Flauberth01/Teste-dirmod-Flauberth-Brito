<?php

namespace Tests\Unit;

use App\Rules\ValidCpf;
use PHPUnit\Framework\TestCase;

class ValidCpfRuleTest extends TestCase
{
    public function test_it_accepts_a_valid_cpf(): void
    {
        $rule = new ValidCpf();
        $failed = false;

        $rule->validate('cpf', '52998224725', function () use (&$failed): void {
            $failed = true;
        });

        $this->assertFalse($failed);
    }

    public function test_it_rejects_an_invalid_cpf(): void
    {
        $rule = new ValidCpf();
        $failed = false;

        $rule->validate('cpf', '12345678900', function () use (&$failed): void {
            $failed = true;
        });

        $this->assertTrue($failed);
    }
}
