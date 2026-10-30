<?php

namespace SysInescolara\Tests\Support;

trait CapturesErrorLog
{
    /**
     * @return array{0: mixed, 1: string}
     */
    protected function captureErrorLog(callable $action): array
    {
        $path = tempnam(sys_get_temp_dir(), 'error-log-');

        if ($path === false) {
            $this->fail('No se pudo crear el archivo temporal para error_log().');
        }

        $previous = ini_set('error_log', $path);

        try {
            $result = $action();
            $contents = (string) file_get_contents($path);
        } finally {
            if ($previous !== false) {
                ini_set('error_log', $previous);
            }

            @unlink($path);
        }

        return [$result, $contents];
    }
}
