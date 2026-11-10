<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class ColaboradorRepository
{
    public function __construct(
        private readonly PDO $pdo
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listByDepartamento(int $departamentoInternalId, ?string $search = null): array
    {
        $params = [':dep_id' => $departamentoInternalId];
        $searchSql = '';

        if ($search !== null && $search !== '') {
            $searchSql = ' AND (c.nome LIKE :q1 OR c.cargo LIKE :q2 OR c.email LIKE :q3)';
            $like = '%' . $search . '%';
            $params[':q1'] = $like;
            $params[':q2'] = $like;
            $params[':q3'] = $like;
        }

        $stmt = $this->pdo->prepare("
            SELECT
                c.*,
                d.public_id AS departamento_public_id,
                d.nome AS departamento_nome,
                d.codigo AS departamento_codigo
            FROM colaboradores c
            INNER JOIN departamentos d ON d.id = c.departamento_id
            WHERE c.departamento_id = :dep_id {$searchSql}
            ORDER BY c.id ASC
        ");
        $stmt->execute($params);

        $rows = $stmt->fetchAll();
        return array_map(fn (array $row): array => self::formatRow($row), $rows);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listAll(?string $search = null): array
    {
        $params = [];
        $whereSql = '';

        if ($search !== null && $search !== '') {
            $whereSql = 'WHERE (c.nome LIKE :q1 OR c.cargo LIKE :q2 OR c.email LIKE :q3)';
            $like = '%' . $search . '%';
            $params[':q1'] = $like;
            $params[':q2'] = $like;
            $params[':q3'] = $like;
        }

        $stmt = $this->pdo->prepare("
            SELECT
                c.*,
                d.public_id AS departamento_public_id,
                d.nome AS departamento_nome,
                d.codigo AS departamento_codigo
            FROM colaboradores c
            INNER JOIN departamentos d ON d.id = c.departamento_id
            {$whereSql}
            ORDER BY c.id ASC
        ");
        $stmt->execute($params);

        $rows = $stmt->fetchAll();
        return array_map(fn (array $row): array => self::formatRow($row), $rows);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByPublicId(string $publicId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                c.*,
                d.public_id AS departamento_public_id,
                d.nome AS departamento_nome,
                d.codigo AS departamento_codigo
            FROM colaboradores c
            INNER JOIN departamentos d ON d.id = c.departamento_id
            WHERE c.public_id = :public_id
            LIMIT 1
        ");
        $stmt->execute([':public_id' => trim($publicId)]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                c.*,
                d.public_id AS departamento_public_id,
                d.nome AS departamento_nome,
                d.codigo AS departamento_codigo
            FROM colaboradores c
            INNER JOIN departamentos d ON d.id = c.departamento_id
            WHERE c.id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    public function existsByEmail(string $email, ?int $excludeId = null): bool
    {
        if ($excludeId !== null) {
            $stmt = $this->pdo->prepare("
                SELECT 1 FROM colaboradores
                WHERE LOWER(email) = LOWER(:email) AND id != :exclude_id
                LIMIT 1
            ");
            $stmt->execute([
                ':email' => $email,
                ':exclude_id' => $excludeId,
            ]);
        } else {
            $stmt = $this->pdo->prepare("
                SELECT 1 FROM colaboradores
                WHERE LOWER(email) = LOWER(:email)
                LIMIT 1
            ");
            $stmt->execute([':email' => $email]);
        }

        return (bool) $stmt->fetchColumn();
    }

    public function countByDepartamento(int $departamentoInternalId): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM colaboradores WHERE departamento_id = :dep_id");
        $stmt->execute([':dep_id' => $departamentoInternalId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function create(int $departamentoInternalId, array $data): array
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO colaboradores (
                public_id, departamento_id, nome, cargo, email, telefone, avatar
            ) VALUES (
                :public_id, :departamento_id, :nome, :cargo, :email, :telefone, :avatar
            )
        ");

        $stmt->execute([
            ':public_id' => $data['public_id'],
            ':departamento_id' => $departamentoInternalId,
            ':nome' => $data['nome'],
            ':cargo' => $data['cargo'],
            ':email' => $data['email'],
            ':telefone' => $data['telefone'],
            ':avatar' => $data['avatar'],
        ]);

        $id = (int) $this->pdo->lastInsertId();
        $row = $this->findById($id);

        return self::formatRow($row ?? []);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function update(int $id, int $departamentoInternalId, array $data): array
    {
        $stmt = $this->pdo->prepare("
            UPDATE colaboradores
            SET
                departamento_id = :departamento_id,
                nome = :nome,
                cargo = :cargo,
                email = :email,
                telefone = :telefone,
                avatar = :avatar
            WHERE id = :id
        ");

        $stmt->execute([
            ':departamento_id' => $departamentoInternalId,
            ':nome' => $data['nome'],
            ':cargo' => $data['cargo'],
            ':email' => $data['email'],
            ':telefone' => $data['telefone'],
            ':avatar' => $data['avatar'],
            ':id' => $id,
        ]);

        $row = $this->findById($id);
        return self::formatRow($row ?? []);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM colaboradores WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    public static function formatRow(array $row): array
    {
        return [
            'id' => (string) ($row['public_id'] ?? ''),
            'departamentoId' => (string) ($row['departamento_public_id'] ?? ''),
            'departamentoNome' => (string) ($row['departamento_nome'] ?? ''),
            'departamentoCodigo' => (string) ($row['departamento_codigo'] ?? ''),
            'nome' => (string) ($row['nome'] ?? ''),
            'cargo' => (string) ($row['cargo'] ?? ''),
            'email' => (string) ($row['email'] ?? ''),
            'telefone' => (string) ($row['telefone'] ?? ''),
            'avatar' => (string) ($row['avatar'] ?? 'CV'),
            'createdAt' => (string) ($row['created_at'] ?? ''),
            'updatedAt' => (string) ($row['updated_at'] ?? ''),
        ];
    }
}

