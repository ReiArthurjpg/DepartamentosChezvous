<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Chez Vous - API de Departamentos & Colaboradores',
    description: 'API RESTful para gerenciamento de departamentos, centros de custo, gestores responsáveis e colaboradores alocados da Chez Vous Produção & Eventos.',
    contact: new OA\Contact(name: 'Engenharia Chez Vous', email: 'engenharia@chezvous.com')
)]
#[OA\Server(url: 'http://localhost:8003', description: 'Servidor Docker Local (Porta 8003)')]
#[OA\SecurityScheme(
    securityScheme: 'AdminApiKey',
    type: 'apiKey',
    in: 'header',
    name: 'X-API-Key',
    description: 'Chave administrativa para gestão de departamentos e colaboradores.'
)]
#[OA\Tag(name: 'Departamentos', description: 'Cadastro, listagem, indicadores, edição e exclusão de departamentos')]
#[OA\Tag(name: 'Colaboradores', description: 'Gestão de pessoas cadastradas e vinculadas a cada departamento')]
#[OA\Tag(name: 'Infraestrutura', description: 'Healthcheck de containers (MySQL + Redis)')]
final class OpenApiSpec
{
}

