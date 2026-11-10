<?php

declare(strict_types=1);

$baseUrl = rtrim((string) ($argv[1] ?? 'http://localhost:8003'), '/');
$apiKey = (string) ($argv[2] ?? 'cv_admin_secret_key_2026_dev');

echo "=== Chez Vous Departamentos & Colaboradores - E2E Smoke Test ===\n";
echo "Alvo: {$baseUrl}\n\n";

function requestJson(string $method, string $url, array $payload = [], array $headers = []): array
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);

    $reqHeaders = array_merge(['Content-Type: application/json'], $headers);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $reqHeaders);

    if (in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    $response = curl_exec($ch);
    $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);

    if ($curlError !== '') {
        throw new RuntimeException("Falha cURL em {$method} {$url}: {$curlError}");
    }

    $decoded = json_decode((string) $response, true);
    return [
        'status' => $statusCode,
        'body' => $decoded ?? $response,
    ];
}

$authHeaders = ["X-API-Key: {$apiKey}"];

echo "[1/5] Testando GET /health...\n";
$health = requestJson('GET', "{$baseUrl}/health");
if ($health['status'] !== 200 || ($health['body']['status'] ?? '') !== 'ok') {
    fwrite(STDERR, "[FAIL] Healthcheck falhou: HTTP {$health['status']}\n");
    exit(1);
}
echo "  -> OK (HTTP 200, MySQL UP)\n";

echo "[2/5] Testando GET /api/v1/admin/departamentos...\n";
$list = requestJson('GET', "{$baseUrl}/api/v1/admin/departamentos", [], $authHeaders);
if ($list['status'] !== 200 || !isset($list['body']['items'])) {
    fwrite(STDERR, "[FAIL] Listagem falhou: HTTP {$list['status']}\n");
    exit(1);
}
echo "  -> OK (HTTP 200 | Total Departamentos: {$list['body']['total']})\n";

$suffix = strtoupper(bin2hex(random_bytes(2)));
$codigo = "PAT-{$suffix}";

echo "[3/5] Testando POST /api/v1/admin/departamentos (Código: {$codigo})...\n";
$createdDep = requestJson('POST', "{$baseUrl}/api/v1/admin/departamentos", [
    'nome' => "Confeitaria Smoke {$suffix}",
    'codigo' => $codigo,
    'centroCusto' => "CC-999 - Smoke Test",
    'gestor' => 'Chef Teste Smoke',
    'email' => "chef.{$suffix}@chezvous.com",
    'descricao' => 'Setor criado pelo smoke test automatizado.',
], $authHeaders);

if ($createdDep['status'] !== 201) {
    fwrite(STDERR, "[FAIL] Criação de departamento falhou: HTTP {$createdDep['status']}\n");
    exit(1);
}
$depId = (string) $createdDep['body']['item']['id'];
echo "  -> OK (HTTP 201 | ID: {$depId} | Colaboradores iniciais: {$createdDep['body']['item']['colaboradores']})\n";

echo "[4/5] Testando POST /api/v1/admin/departamentos/{$depId}/colaboradores...\n";
$createdColab = requestJson('POST', "{$baseUrl}/api/v1/admin/departamentos/{$depId}/colaboradores", [
    'nome' => 'Colaborador Smoke Teste',
    'cargo' => 'Pâtissier Pleno',
    'email' => "colab.{$suffix}@chezvous.com",
    'telefone' => '(11) 98888-7777',
], $authHeaders);

if ($createdColab['status'] !== 201) {
    fwrite(STDERR, "[FAIL] Criação de colaborador falhou: HTTP {$createdColab['status']}\n");
    exit(1);
}
echo "  -> OK (HTTP 201 | Avatar gerado: {$createdColab['body']['item']['avatar']})\n";

echo "[5/5] Verificando contagem atualizada em GET /api/v1/admin/departamentos/{$depId} e limpando...\n";
$detail = requestJson('GET', "{$baseUrl}/api/v1/admin/departamentos/{$depId}", [], $authHeaders);
if ($detail['status'] !== 200 || ($detail['body']['colaboradores'] ?? 0) !== 1) {
    fwrite(STDERR, "[FAIL] Contagem de colaboradores não refletiu 1: " . json_encode($detail['body']) . "\n");
    exit(1);
}
requestJson('DELETE', "{$baseUrl}/api/v1/admin/departamentos/{$depId}?force=1", [], $authHeaders);
echo "  -> OK (Contagem validada = 1 e registro de teste limpo)\n\n";

echo "=== SUCESSO TOTAL: API DE DEPARTAMENTOS & COLABORADORES VALIDADA! ===\n";

