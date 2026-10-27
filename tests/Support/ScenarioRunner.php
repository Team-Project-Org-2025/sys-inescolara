<?php

namespace SysInescolara\Tests\Support;

/**
 * Ejecuta escenarios de un harness de controlador en subproceso.
 *
 * La clase que lo use debe definir la constante HARNESS con la ruta al
 * script harness (php <HARNESS> <escenario>).
 */
trait ScenarioRunner
{
    /**
     * @return array{stdout: string, stderr: string, status: string|null, exit: int}
     */
    private function runScenario(string $name): array
    {
        $descriptors = [
            0 => ['file', '/dev/null', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $process = proc_open([PHP_BINARY, static::HARNESS, $name], $descriptors, $pipes);
        $this->assertIsResource($process, "No se pudo lanzar el escenario '$name'");

        $stdout = (string) stream_get_contents($pipes[1]);
        $stderr = (string) stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($process);

        preg_match('/HARNESS_STATUS:(\S+)/', $stderr, $matches);

        return [
            'stdout' => $stdout,
            'stderr' => $stderr,
            'status' => $matches[1] ?? null,
            'exit'   => $exit,
        ];
    }

    /**
     * @return array{stdout: string, stderr: string, status: string|null, exit: int, json: array<string, mixed>}
     */
    private function runJsonScenario(string $name): array
    {
        $result = $this->runScenario($name);
        $this->assertSame(0, $result['exit'], "Escenario '$name' terminó con errores:\n{$result['stderr']}");
        $this->assertStringNotContainsString('Fatal error', $result['stderr']);
        $this->assertStringNotContainsString('Uncaught', $result['stderr']);

        $json = json_decode($result['stdout'], true);
        $this->assertIsArray($json, "Escenario '$name' no respondió JSON: {$result['stdout']}");

        $result['json'] = $json;
        return $result;
    }
}
