<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class DepartamentoRepository
{
    public function __construct(
        private readonly PDO $pdo
    ) {
    }

    /**
     * @return array{
     *   totalDepartamentos: int,
     *   departamentosAtivos: int,
     *   departamentosInativos: int,
     *   totalColaboradores: int
     * }
     */
    public function getStats(): array
    {
        $row = $this->pdo->query("
            SELECT
                COUNT(*) AS total_departamentos,
                SUM(CASE WHEN status = 'Ativo' THEN 1 ELSE 0 END) AS ativos,
                SUM(CASE WHEN status = 'Inativo' THEN 1 ELSE 0 END) AS inativos
            FROM departamentos
        ")->fetch();

        $totalColabs = (int) $this->pdo->query("SELECT COUNT(*) FROM colaboradores")->fetchColumn();

        return [
            'totalDepartamentos' => (int) ($row['total_departamentos'] ?? 0),
            'departamentosAtivos' => (int) ($row['ativos'] ?? 0),
            'departamentosInativos' => (int) ($row['inativos'] ?? 0),
            'totalColaboradores' => $totalColabs,
        ];
    }

    /**
     * @return array{total: int, items: array<int, array<string, mixed>>}
     */
    public function paginate(
        ?string $search,
        ?string $status,
        string $sort,
        int $page,
        int $perPage
    ): array {
        $conditions = [];
        $params = [];

        if ($status !== null && $status !== '' && in_array($status, ['Ativo', 'Inativo'], true)) {
            $conditions[] = 'd.status = :status';
            $params[':status'] = $status;
        }

        if ($search !== null && $search !== '') {
            $conditions[] = '(d.nome LIKE :q1 OR d.codigo LIKE :q2 OR d.gestor LIKE :q3 OR d.centro_custo LIKE :q4)';
            $like = '%' . $search . '%';
            $params[':q1'] = $like;
            $params[':q2'] = $like;
            $params[':q3'] = $like;
            $params[':q4'] = $like;
        }

        $whereSql = $conditions !== [] ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM departamentos d {$whereSql}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $orderSql = match ($sort) {
            'nome', 'name' => 'd.nome ASC',
            'codigo' => 'd.codigo ASC',
            'colaboradores' => 'colaboradores_count DESC, d.nome ASC',
            default => 'd.id ASC',
        };

        $offset = max(0, ($page - 1) * $perPage);
        $sql = "
            SELECT
                d.*,
                (SELECT COUNT(*) FROM colaboradores c WHERE c.departamento_id = d.id) AS colaboradores_count
            FROM departamentos d
            {$whereSql}
            ORDER BY {$orderSql}
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll();
        $items = array_map(fn (array $row): array => self::formatRow($row), $rows);

        return [
            'total' => $total,
            'items' => $items,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByPublicIdOrCodigo(string $idOrCodigo): ?array
    {
        $trimmed = trim($idOrCodigo);
        if ($trimmed === '') {
            return null;
        }

        $stmt = $this->pdo->prepare("
            SELECT
                d.*,
                (SELECT COUNT(*) FROM colaboradores c WHERE c.departamento_id = d.id) AS colaboradores_count
            FROM departamentos d
            WHERE LOWER(d.public_id) = LOWER(:id1) OR LOWER(d.codigo) = LOWER(:id2)
            LIMIT 1
        ");
        $stmt->execute([
            ':id1' => $trimmed,
            ':id2' => $trimmed,
        ]);
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
                d.*,
                (SELECT COUNT(*) FROM colaboradores c WHERE c.departamento_id = d.id) AS colaboradores_count
            FROM departamentos d
            WHERE d.id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    public function existsByCodigo(string $codigo, ?int $excludeId = null): bool
    {
        if ($excludeId !== null) {
            $stmt = $this->pdo->prepare("
                SELECT 1 FROM departamentos
                WHERE LOWER(codigo) = LOWER(:codigo) AND id != :exclude_id
                LIMIT 1
            ");
            $stmt->execute([
                ':codigo' => $codigo,
                ':exclude_id' => $excludeId,
            ]);
        } else {
            $stmt = $this->pdo->prepare("
                SELECT 1 FROM departamentos
                WHERE LOWER(codigo) = LOWER(:codigo)
                LIMIT 1
            ");
            $stmt->execute([':codigo' => $codigo]);
        }

        return (bool) $stmt->fetchColumn();
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO departamentos (
                public_id, nome, codigo, centro_custo, gestor, email, descricao, status
            ) VALUES (
                :public_id, :nome, :codigo, :centro_custo, :gestor, :email, :descricao, :status
            )
        ");

        $stmt->execute([
            ':public_id' => $data['public_id'],
            ':nome' => $data['nome'],
            ':codigo' => $data['codigo'],
            ':centro_custo' => $data['centro_custo'],
            ':gestor' => $data['gestor'],
            ':email' => $data['email'],
            ':descricao' => $data['descricao'],
            ':status' => $data['status'],
        ]);

        $id = (int) $this->pdo->lastInsertId();
        $row = $this->findById($id);

        return self::formatRow($row ?? []);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function update(int $id, array $data): array
    {
        $stmt = $this->pdo->prepare("
            UPDATE departamentos
            SET
                nome = :nome,
                codigo = :codigo,
                centro_custo = :centro_custo,
                gestor = :gestor,
                email = :email,
                descricao = :descricao,
                status = :status
            WHERE id = :id
        ");

        $stmt->execute([
            ':nome' => $data['nome'],
            ':codigo' => $data['codigo'],
            ':centro_custo' => $data['centro_custo'],
            ':gestor' => $data['gestor'],
            ':email' => $data['email'],
            ':descricao' => $data['descricao'],
            ':status' => $data['status'],
            ':id' => $id,
        ]);

        $row = $this->findById($id);
        return self::formatRow($row ?? []);
    }

    /**
     * @return array<string, mixed>
     */
    public function updateStatus(int $id, string $status): array
    {
        $stmt = $this->pdo->prepare("UPDATE departamentos SET status = :status WHERE id = :id");
        $stmt->execute([
            ':status' => $status,
            ':id' => $id,
        ]);

        $row = $this->findById($id);
        return self::formatRow($row ?? []);
    }

    public function delete(int $id, bool $forceCascade = false): void
    {
        $this->pdo->beginTransaction();
        try {
            if ($forceCascade) {
                $delColab = $this->pdo->prepare("DELETE FROM colaboradores WHERE departamento_id = :dep_id");
                $delColab->execute([':dep_id' => $id]);
            }
            $stmt = $this->pdo->prepare("DELETE FROM departamentos WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $this->pdo->commit();
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    public static function formatRow(array $row): array
    {
        $createdAtRaw = (string) ($row['created_at'] ?? date('Y-m-d H:i:s'));
        $ts = strtotime($createdAtRaw);
        $criadoEm = $ts !== false ? date('d/m/Y', $ts) : date('d/m/Y');
        $count = (int) ($row['colaboradores_count'] ?? 0);

        return [
            'id' => (string) ($row['public_id'] ?? ''),
            'nome' => (string) ($row['nome'] ?? ''),
            'codigo' => (string) ($row['codigo'] ?? ''),
            'gestor' => (string) ($row['gestor'] ?? ''),
            'email' => (string) ($row['email'] ?? ''),
            'centroCusto' => (string) ($row['centro_custo'] ?? ''),
            'colaboradores' => $count,
            'colaboradoresCount' => $count,
            'descricao' => (string) ($row['descricao'] ?? ''),
            'status' => (string) ($row['status'] ?? 'Ativo'),
            'criadoEm' => $criadoEm,
            'createdAt' => $createdAtRaw,
            'updatedAt' => (string) ($row['updated_at'] ?? $createdAtRaw),
        ];
    }
}

