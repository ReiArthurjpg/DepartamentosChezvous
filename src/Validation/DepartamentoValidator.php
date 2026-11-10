<?php

declare(strict_types=1);

namespace App\Validation;

use App\Exception\ApiException;

final class DepartamentoValidator
{
    private const ALLOWED_STATUSES = ['Ativo', 'Inativo'];

    /**
     * @param array<string, mixed> $input
     * @return array{
     *   nome: string,
     *   codigo: string,
     *   centro_custo: string,
     *   gestor: string,
     *   email: string,
     *   descricao: string,
     *   status: string
     * }
     */
    public function validate(array $input, ?array $existing = null): array
    {
        $nome = trim((string) ($input['nome'] ?? $existing['nome'] ?? ''));
        $codigo = strtoupper(trim((string) ($input['codigo'] ?? $existing['codigo'] ?? '')));
        $centroCusto = trim((string) (
            $input['centroCusto']
            ?? $input['centro_custo']
            ?? $existing['centro_custo']
            ?? ''
        ));
        $gestor = trim((string) ($input['gestor'] ?? $existing['gestor'] ?? ''));
        $email = strtolower(trim((string) ($input['email'] ?? $existing['email'] ?? '')));
        $descricao = trim((string) ($input['descricao'] ?? $existing['descricao'] ?? ''));
        $statusRaw = trim((string) ($input['status'] ?? $existing['status'] ?? 'Ativo'));

        $errors = [];

        if (mb_strlen($nome) < 2) {
            $errors['nome'] = 'Informe o nome do departamento (mínimo 2 caracteres).';
        } elseif (mb_strlen($nome) > 160) {
            $errors['nome'] = 'O nome do departamento não pode exceder 160 caracteres.';
        }

        if (mb_strlen($codigo) < 2) {
            $errors['codigo'] = 'Informe o código/sigla do departamento (ex: GAST-01).';
        } elseif (mb_strlen($codigo) > 32) {
            $errors['codigo'] = 'O código do departamento não pode exceder 32 caracteres.';
        }

        if (mb_strlen($centroCusto) < 2) {
            $errors['centroCusto'] = 'Informe o centro de custo do departamento.';
        } elseif (mb_strlen($centroCusto) > 160) {
            $errors['centroCusto'] = 'O centro de custo não pode exceder 160 caracteres.';
        }

        if (mb_strlen($gestor) < 2) {
            $errors['gestor'] = 'Informe o nome do gestor responsável.';
        } elseif (mb_strlen($gestor) > 160) {
            $errors['gestor'] = 'O nome do gestor não pode exceder 160 caracteres.';
        }

        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'Informe um e-mail corporativo válido.';
        }

        $status = in_array($statusRaw, self::ALLOWED_STATUSES, true) ? $statusRaw : '';
        if ($status === '') {
            $errors['status'] = 'Status inválido. Valores permitidos: Ativo, Inativo.';
        }

        if ($errors !== []) {
            throw ApiException::validation($errors);
        }

        return [
            'nome' => $nome,
            'codigo' => $codigo,
            'centro_custo' => $centroCusto,
            'gestor' => $gestor,
            'email' => $email,
            'descricao' => $descricao,
            'status' => $status,
        ];
    }

    public function validateStatus(string $status): string
    {
        $trimmed = ucfirst(strtolower(trim($status)));
        if (!in_array($trimmed, self::ALLOWED_STATUSES, true)) {
            throw ApiException::validation([
                'status' => 'Status inválido. Use Ativo ou Inativo.',
            ]);
        }
        return $trimmed;
    }
}

