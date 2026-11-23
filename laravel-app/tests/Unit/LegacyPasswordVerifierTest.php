<?php

namespace Tests\Unit;

use App\Services\LegacyPasswordVerifier;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LegacyPasswordVerifierTest extends TestCase
{
    #[Test]
    public function it_verifies_werkzeug_scrypt_hashes(): void
    {
        $hash = 'scrypt:1024:8:16$NaCl$fdbabe1c9d3472007856e7190d01e9fe'
            .'7c6ad7cbc8237830e77376634b3731622eaf30d92e22a3886ff109279d9830da'
            .'c727afb94a83ee6d8360cbdfa2cc0640';

        $verifier = new LegacyPasswordVerifier;

        $this->assertTrue($verifier->verify('password', $hash));
        $this->assertFalse($verifier->verify('incorrect', $hash));
    }

    #[Test]
    public function it_rejects_malformed_hashes_without_running_scrypt(): void
    {
        $verifier = new LegacyPasswordVerifier;

        $this->assertFalse($verifier->verify('password', 'not-a-valid-hash'));
        $this->assertFalse($verifier->verify(
            'password',
            'scrypt:999999999:8:1$salt$'.str_repeat('a', 128),
        ));
    }
}
