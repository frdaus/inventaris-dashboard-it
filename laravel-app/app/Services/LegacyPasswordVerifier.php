<?php

namespace App\Services;

use RuntimeException;

class LegacyPasswordVerifier
{
    public function verify(string $password, string $storedHash): bool
    {
        if (str_starts_with($storedHash, '$2y$')
            || str_starts_with($storedHash, '$argon2i$')
            || str_starts_with($storedHash, '$argon2id$')) {
            return password_verify($password, $storedHash);
        }

        $parts = explode('$', $storedHash);
        if (count($parts) !== 3 || $parts[1] === '' || ! ctype_xdigit($parts[2])) {
            return false;
        }

        if (preg_match('/^pbkdf2:(sha256|sha512):([0-9]+)$/', $parts[0], $matches)) {
            $iterations = (int) $matches[2];
            if ($iterations < 1_000 || $iterations > 5_000_000) {
                return false;
            }

            $derived = hash_pbkdf2(
                $matches[1],
                $password,
                $parts[1],
                $iterations,
                intdiv(strlen($parts[2]), 2),
            );

            return hash_equals(strtolower($parts[2]), strtolower($derived));
        }

        if (preg_match('/^scrypt:([0-9]+):([0-9]+):([0-9]+)$/', $parts[0], $matches)) {
            $cost = (int) $matches[1];
            $blockSize = (int) $matches[2];
            $parallelization = (int) $matches[3];
            if ($cost < 2 || ($cost & ($cost - 1)) !== 0
                || $cost > 1_048_576 || $blockSize < 1 || $blockSize > 32
                || $parallelization < 1 || $parallelization > 16) {
                return false;
            }

            $derived = $this->deriveScrypt(
                $password,
                $parts[1],
                strlen($parts[2]) / 2,
                $cost,
                $blockSize,
                $parallelization,
            );

            return hash_equals(strtolower($parts[2]), strtolower($derived));
        }

        return false;
    }

    private function deriveScrypt(
        string $password,
        string $salt,
        int $length,
        int $cost,
        int $blockSize,
        int $parallelization,
    ): string {
        $command = [
            'openssl',
            'kdf',
            '-keylen',
            (string) $length,
            '-kdfopt',
            'hexpass:'.bin2hex($password),
            '-kdfopt',
            'hexsalt:'.bin2hex($salt),
            '-kdfopt',
            'n:'.$cost,
            '-kdfopt',
            'r:'.$blockSize,
            '-kdfopt',
            'p:'.$parallelization,
            '-kdfopt',
            'maxmem_bytes:67108864',
            'SCRYPT',
        ];
        $pipes = [];
        $process = proc_open(
            $command,
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
            null,
            null,
            ['bypass_shell' => true],
        );

        if (! is_resource($process)) {
            throw new RuntimeException('Unable to start OpenSSL for legacy password verification.');
        }

        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            report(new RuntimeException('OpenSSL could not verify a legacy password hash: '.trim($error)));

            return '';
        }

        return strtolower(str_replace(':', '', trim($output)));
    }
}
