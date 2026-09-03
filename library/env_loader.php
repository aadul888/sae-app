<?php
if (!function_exists('sae_env_paths')) {
    function sae_env_paths(): array
    {
        $root = __DIR__ . '/..';
        return [
            $root . '/.env',
            $root . '/content/.env',
        ];
    }
}

if (!function_exists('sae_env_path')) {
    function sae_env_path(): string
    {
        foreach (sae_env_paths() as $path) {
            if (is_file($path)) {
                return $path;
            }
        }
        return '';
    }
}

if (!function_exists('sae_load_env')) {
    function sae_load_env(): void
    {
        $path = sae_env_path();
        if ($path === '' || !is_file($path)) {
            return;
        }

        $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }

            [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
            $key = trim($key);
            $value = trim($value);
            if ($key === '') {
                continue;
            }

            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

sae_load_env();
