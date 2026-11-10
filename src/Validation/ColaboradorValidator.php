<?php

declare(strict_types=1);

namespace App\Validation;

use App\Exception\ApiException;

final class ColaboradorValidator
{
    private const ALLOWED_STATUSES = ['Ativo', 'Inativo'];

    /**
     * @param array<string, mixed> $input
     * @return array{
     *   nome: string,
     *   cargo: string,
     *   email: string,
     *   telefone: string,
     *   avatar: string,
     *   status: string
     * }
     */
    public function validate(array $input, ?array $existing = null): array
    {
        $nome = trim((string) ($input['nome'] ?? $existing['nome'] ?? ''));
        $cargo = trim((string) ($input['cargo'] ?? $existing['cargo'] ?? ''));
        $email = strtolower(trim((string) ($input['email'] ?? $existing['email'] ?? '')));
        $telefone = trim((string) ($input['telefone'] ?? $existing['telefone'] ?? ''));
        $statusRaw = trim((string) ($input['status'] ?? $existing['status'] ?? 'Ativo'));
        $customAvatar = strtoupper(trim((string) ($input['avatar'] ?? '')));

        $errors = [];

        if (mb_strlen($nome) < 2) {
            $errors['nome'] = 'Informe o nome completo do colaborador (mínimo 2 caracteres).';
        } elseif (mb_strlen($nome) > 160) {
            $errors['nome'] = 'O nome do colaborador não pode exceder 160 caracteres.';
        }

        if (mb_strlen($cargo) < 2) {
            $errors['cargo'] = 'Informe o cargo ou função no setor.';
        } elseif (mb_strlen($cargo) > 160) {
            $errors['cargo'] = 'O cargo não pode exceder 160 caracteres.';
        }

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'Informe um e-mail corporativo válido.';
        }

        $digitsPhone = preg_replace('/\D+/', '', $telefone) ?? '';
        if (strlen($digitsPhone) < 8) {
            $errors['telefone'] = 'Informe um telefone ou WhatsApp válido com DDD.';
        } elseif (mb_strlen($telefone) > 30) {
            $errors['telefone'] = 'O telefone não pode exceder 30 caracteres.';
        }

        $status = in_array($statusRaw, self::ALLOWED_STATUSES, true) ? $statusRaw : '';
        if ($status === '') {
            $errors['status'] = 'Status inválido. Valores permitidos: Ativo, Inativo.';
        }

        if ($errors !== []) {
            throw ApiException::validation($errors);
        }

        $avatar = $customAvatar !== '' ? mb_substr($customAvatar, 0, 4) : self::deriveAvatar($nome);

        return [
            'nome' => $nome,
            'cargo' => $cargo,
            'email' => $email,
            'telefone' => $telefone,
            'avatar' => $avatar,
            'status' => $status,
        ];
    }

    public static function deriveAvatar(string $nome): string
    {
        $clean = trim($nome);
        if ($clean === '') {
            return 'CV';
        }
        $parts = preg_split('/\s+/u', $clean, -1, PREG_SPLIT_NO_EMPTY);
        if (!is_array($parts) || $parts === []) {
            return 'CV';
        }
        if (count($parts) >= 2) {
            $first = mb_substr($parts[0], 0, 1);
            $last = mb_substr($parts[count($parts) - 1], 0, 1);
            return mb_strtoupper($first . $last);
        }
        return mb_strtoupper(mb_substr($clean, 0, 2));
    }
}

