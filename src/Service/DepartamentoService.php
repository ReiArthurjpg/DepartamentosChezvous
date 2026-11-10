<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\ApiException;
use App\Repository\ColaboradorRepository;
use App\Repository\DepartamentoRepository;
use App\Validation\DepartamentoValidator;

final class DepartamentoService
{
    public function __construct(
        private readonly DepartamentoRepository $departamentoRepo,
        private readonly ColaboradorRepository $colaboradorRepo,
        private readonly DepartamentoValidator $validator
    ) {
    }

    /**
     * @return array<string, int>
     */
    public function getStats(): array
    {
        return $this->departamentoRepo->getStats();
    }

    /**
     * @return array{
     *   page: int,
     *   perPage: int,
     *   total: int,
     *   nextSuggestedCodigo: string,
     *   stats: array<string, int>,
     *   items: array<int, array<string, mixed>>
     * }
     */
    public function list(
        ?string $search,
        string $sort = 'default',
        int $page = 1,
        int $perPage = 100
    ): array {
        $page = max(1, $page);
        $perPage = min(200, max(1, $perPage));

        $result = $this->departamentoRepo->paginate($search, $sort, $page, $perPage);
        $stats = $this->departamentoRepo->getStats();
        $nextSuggestedCodigo = $this->departamentoRepo->getNextSuggestedCodigo();

        return [
            'page' => $page,
            'perPage' => $perPage,
            'total' => $result['total'],
            'nextSuggestedCodigo' => $nextSuggestedCodigo,
            'stats' => $stats,
            'items' => $result['items'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getDetail(string $idOrCodigo): array
    {
        $row = $this->departamentoRepo->findByPublicIdOrCodigo($idOrCodigo);
        if ($row === null) {
            throw ApiException::notFound('Departamento não encontrado.');
        }

        $dep = DepartamentoRepository::formatRow($row);
        $dep['pessoas'] = $this->colaboradorRepo->listByDepartamento((int) $row['id']);

        return $dep;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function create(array $payload): array
    {
        $validated = $this->validator->validate($payload);

        if ($this->departamentoRepo->existsByCodigo($validated['codigo'])) {
            throw ApiException::conflict(
                "Já existe um departamento cadastrado com o código \"{$validated['codigo']}\".",
                'DUPLICATE_CODIGO',
                ['codigo' => 'Este código/sigla já está em uso por outro departamento.']
            );
        }

        $validated['public_id'] = self::generateUuidV4();

        return $this->departamentoRepo->create($validated);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function update(string $idOrCodigo, array $payload): array
    {
        $existing = $this->departamentoRepo->findByPublicIdOrCodigo($idOrCodigo);
        if ($existing === null) {
            throw ApiException::notFound('Departamento não encontrado.');
        }

        $internalId = (int) $existing['id'];
        $validated = $this->validator->validate($payload, $existing);

        if ($this->departamentoRepo->existsByCodigo($validated['codigo'], $internalId)) {
            throw ApiException::conflict(
                "Já existe outro departamento cadastrado com o código \"{$validated['codigo']}\".",
                'DUPLICATE_CODIGO',
                ['codigo' => 'Este código/sigla já está em uso por outro departamento.']
            );
        }

        return $this->departamentoRepo->update($internalId, $validated);
    }

    public function delete(string $idOrCodigo, bool $forceCascade = false): void
    {
        $existing = $this->departamentoRepo->findByPublicIdOrCodigo($idOrCodigo);
        if ($existing === null) {
            throw ApiException::notFound('Departamento não encontrado.');
        }

        $internalId = (int) $existing['id'];
        $colabCount = $this->colaboradorRepo->countByDepartamento($internalId);

        if ($colabCount > 0 && !$forceCascade) {
            throw ApiException::conflict(
                "Não é possível excluir o departamento \"{$existing['nome']}\" pois existem {$colabCount} colaboradores vinculados a ele.",
                'DEPARTMENT_HAS_COLABORADORES'
            );
        }

        $this->departamentoRepo->delete($internalId, $forceCascade);
    }

    public static function generateUuidV4(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }
}

