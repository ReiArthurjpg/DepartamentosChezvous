<?php

declare(strict_types=1);

namespace Tests;

use App\Exception\ApiException;
use App\Validation\ColaboradorValidator;
use App\Validation\DepartamentoValidator;
use PHPUnit\Framework\TestCase;

final class ValidatorsTest extends TestCase
{
    public function testDepartamentoValidatorSuccess(): void
    {
        $validator = new DepartamentoValidator();
        $result = $validator->validate([
            'nome' => 'Pâtisserie & Confeitaria',
            'codigo' => 'pat-07',
            'centroCusto' => 'CC-107 - Doces Finos',
            'gestor' => 'Beatriz Fagundes',
            'email' => 'BEATRIZ@CHEZVOUS.COM',
            'descricao' => 'Confeitaria artística e sobremesas de eventos.',
            'status' => 'Ativo',
        ]);

        self::assertSame('Pâtisserie & Confeitaria', $result['nome']);
        self::assertSame('PAT-07', $result['codigo']);
        self::assertSame('CC-107 - Doces Finos', $result['centro_custo']);
        self::assertSame('Beatriz Fagundes', $result['gestor']);
        self::assertSame('beatriz@chezvous.com', $result['email']);
        self::assertSame('Ativo', $result['status']);
    }

    public function testDepartamentoValidatorRejectsEmptyFields(): void
    {
        $this->expectException(ApiException::class);
        $validator = new DepartamentoValidator();
        $validator->validate([
            'nome' => '',
            'codigo' => '',
            'centroCusto' => '',
            'gestor' => '',
        ]);
    }

    public function testDepartamentoValidatorRejectsInvalidEmail(): void
    {
        $this->expectException(ApiException::class);
        $validator = new DepartamentoValidator();
        $validator->validate([
            'nome' => 'Pâtisserie',
            'codigo' => 'PAT-07',
            'centroCusto' => 'CC-107',
            'gestor' => 'Beatriz',
            'email' => 'email-invalido',
        ]);
    }

    public function testColaboradorValidatorSuccessAndDeriveAvatar(): void
    {
        $validator = new ColaboradorValidator();
        $result = $validator->validate([
            'nome' => 'Rodrigo Almeida',
            'cargo' => 'Cozinheiro Sênior',
            'email' => 'RODRIGO.ALMEIDA@CHEZVOUS.COM',
            'telefone' => '(11) 98765-4321',
        ]);

        self::assertSame('Rodrigo Almeida', $result['nome']);
        self::assertSame('Cozinheiro Sênior', $result['cargo']);
        self::assertSame('rodrigo.almeida@chezvous.com', $result['email']);
        self::assertSame('RA', $result['avatar']);
        self::assertSame('Ativo', $result['status']);
    }

    public function testColaboradorValidatorRejectsInvalidPhone(): void
    {
        $this->expectException(ApiException::class);
        $validator = new ColaboradorValidator();
        $validator->validate([
            'nome' => 'Rodrigo Almeida',
            'cargo' => 'Cozinheiro Sênior',
            'email' => 'rodrigo@chezvous.com',
            'telefone' => '123',
        ]);
    }

    public function testMigrationsFolderHasValidSql(): void
    {
        $dir = dirname(__DIR__) . '/database/migrations';
        self::assertDirectoryExists($dir);
        $files = glob($dir . '/*.sql');
        self::assertIsArray($files);
        self::assertNotEmpty($files);
        foreach ($files as $file) {
            self::assertFileExists($file);
            self::assertGreaterThan(0, (int) filesize($file));
        }
    }
}

