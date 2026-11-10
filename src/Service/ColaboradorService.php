<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\ApiException;
use App\Repository\ColaboradorRepository;
use App\Repository\DepartamentoRepository;
use App\Validation\ColaboradorValidator;

final class ColaboradorService
{
    public function __construct(
        private readonly ColaboradorRepository $colaboradorRepo,
        private readonly DepartamentoRepository $departamentoRepo,
        private readonly ColaboradorValidator $validator
    ) {
    }

    /**
     * @return array{
     *   departamento: array<string, mixed>,
     *   total: int,
     *   items: array<int, array<string, mixed>>
     * }
     */
    public function listByDepartamento(string $depIdOrCodigo, ?string $search = null): array
    {
        $depRow = $this->departamentoRepo->findByPublicIdOrCodigo($depIdOrCodigo);
        if ($depRow === null) {
            throw ApiException::notFound('Departamento não encontrado.');
        }

        $items = $this->colaboradorRepo->listByDepartamento((int) $depRow['id'], $search);

        return [
            'departamento' => DepartamentoRepository::formatRow($depRow),
            'total' => count($items),
            'items' => $items,
        ];
    }

    /**
     * @return array{total: int, items: array<int, array<string, mixed>>}
     */
    public function listAll(?string $search = null): array
    {
        $items = $this->colaboradorRepo->listAll($search);
        return [
            'total' => count($items),
            'items' => $items,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function create(string $depIdOrCodigo, array $payload): array
    {
        $depRow = $this->departamentoRepo->findByPublicIdOrCodigo($depIdOrCodigo);
        if ($depRow === null) {
            throw ApiException::notFound('Departamento não encontrado.');
        }

        $validated = $this->validator->validate($payload);

        if ($this->colaboradorRepo->existsByEmail($validated['email'])) {
            throw ApiException::conflict(
                "Já existe um colaborador cadastrado com o e-mail \"{$validated['email']}\".",
                'DUPLICATE_EMAIL',
                ['email' => 'Este e-mail já está vinculado a outro colaborador.']
            );
        }

        $validated['public_id'] = DepartamentoService::generateUuidV4();

        return $this->colaboradorRepo->create((int) $depRow['id'], $validated);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function update(string $colabPublicId, array $payload): array
    {
        $existing = $this->colaboradorRepo->findByPublicId($colabPublicId);
        if ($existing === null) {
            throw ApiException::notFound('Colaborador não encontrado.');
        }

        $internalId = (int) $existing['id'];
        $targetDepInternalId = (int) $existing['departamento_id'];

        $newDepIdOrCodigo = trim((string) ($payload['departamentoId'] ?? $payload['departamento_id'] ?? ''));
        if ($newDepIdOrCodigo !== '') {
            $depRow = $this->departamentoRepo->findByPublicIdOrCodigo($newDepIdOrCodigo);
            if ($depRow === null) {
                throw ApiException::notFound('Departamento de destino não encontrado.');
            }
            $targetDepInternalId = (int) $depRow['id'];
        }

        $validated = $this->validator->validate($payload, $existing);

        if ($this->colaboradorRepo->existsByEmail($validated['email'], $internalId)) {
            throw ApiException::conflict(
                "Já existe outro colaborador cadastrado com o e-mail \"{$validated['email']}\".",
                'DUPLICATE_EMAIL',
                ['email' => 'Este e-mail já está vinculado a outro colaborador.']
            );
        }

        return $this->colaboradorRepo->update($internalId, $targetDepInternalId, $validated);
    }

    public function delete(string $colabPublicId): void
    {
        $existing = $this->colaboradorRepo->findByPublicId($colabPublicId);
        if ($existing === null) {
            throw ApiException::notFound('Colaborador não encontrado.');
        }

        $this->colaboradorRepo->delete((int) $existing['id']);
    }
}

