<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\DepartamentoService;
use App\Support\JsonResponse;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class DepartamentoController
{
    public function __construct(
        private readonly DepartamentoService $service
    ) {
    }

    #[OA\Get(
        path: '/api/v1/admin/departamentos/stats',
        operationId: 'adminGetDepartamentoStats',
        summary: 'Retorna os indicadores gerais de departamentos e colaboradores',
        tags: ['Departamentos'],
        security: [['AdminApiKey' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Contadores gerais'),
            new OA\Response(response: 401, description: 'X-API-Key inválida'),
        ]
    )]
    public function stats(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return JsonResponse::send($response, $this->service->getStats());
    }

    #[OA\Get(
        path: '/api/v1/admin/departamentos',
        operationId: 'adminListDepartamentos',
        summary: 'Lista departamentos de forma paginada com busca, ordenação e contagem de colaboradores',
        tags: ['Departamentos'],
        security: [['AdminApiKey' => []]],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'sort', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['default', 'recent', 'nome', 'codigo', 'colaboradores'])),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'perPage', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 100)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lista paginada de departamentos'),
            new OA\Response(response: 401, description: 'X-API-Key inválida'),
        ]
    )]
    public function list(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $query = $request->getQueryParams();
        $search = isset($query['search'])
            ? trim((string) $query['search'])
            : (isset($query['q']) ? trim((string) $query['q']) : null);
        $sort = isset($query['sort']) ? trim((string) $query['sort']) : 'default';
        $page = max(1, (int) ($query['page'] ?? 1));
        $perPage = min(200, max(1, (int) ($query['perPage'] ?? 100)));

        $result = $this->service->list($search, $sort, $page, $perPage);
        return JsonResponse::send($response, $result);
    }

    #[OA\Get(
        path: '/api/v1/admin/departamentos/{id}',
        operationId: 'adminGetDepartamentoDetail',
        summary: 'Obtém detalhes completos do departamento (por UUID ou código) e lista de pessoas cadastradas',
        tags: ['Departamentos'],
        security: [['AdminApiKey' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Detalhes do departamento'),
            new OA\Response(response: 404, description: 'Departamento não encontrado'),
        ]
    )]
    public function detail(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (string) ($args['id'] ?? '');
        return JsonResponse::send($response, $this->service->getDetail($id));
    }

    #[OA\Post(
        path: '/api/v1/admin/departamentos',
        operationId: 'adminCreateDepartamento',
        summary: 'Cadastra um novo departamento',
        tags: ['Departamentos'],
        security: [['AdminApiKey' => []]],
        responses: [
            new OA\Response(response: 201, description: 'Departamento cadastrado com sucesso'),
            new OA\Response(response: 409, description: 'Código já cadastrado'),
            new OA\Response(response: 422, description: 'Erro de validação'),
        ]
    )]
    public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $body = self::extractBody($request);
        $created = $this->service->create($body);

        return JsonResponse::send($response, [
            'message' => 'Departamento cadastrado com sucesso.',
            'item' => $created,
        ], 201);
    }

    #[OA\Put(
        path: '/api/v1/admin/departamentos/{id}',
        operationId: 'adminUpdateDepartamento',
        summary: 'Atualiza os dados de um departamento',
        tags: ['Departamentos'],
        security: [['AdminApiKey' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Departamento atualizado com sucesso'),
            new OA\Response(response: 404, description: 'Departamento não encontrado'),
            new OA\Response(response: 409, description: 'Código já em uso'),
            new OA\Response(response: 422, description: 'Erro de validação'),
        ]
    )]
    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (string) ($args['id'] ?? '');
        $body = self::extractBody($request);
        $updated = $this->service->update($id, $body);

        return JsonResponse::send($response, [
            'message' => 'Departamento atualizado com sucesso.',
            'item' => $updated,
        ]);
    }

    #[OA\Delete(
        path: '/api/v1/admin/departamentos/{id}',
        operationId: 'adminDeleteDepartamento',
        summary: 'Exclui um departamento',
        tags: ['Departamentos'],
        security: [['AdminApiKey' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'force', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Departamento removido'),
            new OA\Response(response: 404, description: 'Departamento não encontrado'),
            new OA\Response(response: 409, description: 'Departamento possui colaboradores vinculados'),
        ]
    )]
    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (string) ($args['id'] ?? '');
        $query = $request->getQueryParams();
        $force = filter_var($query['force'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $this->service->delete($id, $force);

        return JsonResponse::send($response, [
            'message' => 'Departamento removido com sucesso.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private static function extractBody(ServerRequestInterface $request): array
    {
        $parsed = $request->getParsedBody();
        if (is_array($parsed)) {
            return $parsed;
        }
        $raw = (string) $request->getBody();
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }
}

