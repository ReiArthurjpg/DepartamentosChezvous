# Arquitetura da API de Departamentos & Colaboradores (Chez Vous)

A API segue exatamente o padrão arquitetural em camadas da **LogMovy**:

```
src/
├── Controller/     # DepartamentoController, ColaboradorController, HealthController
├── Service/        # DepartamentoService, ColaboradorService
├── Repository/     # DepartamentoRepository, ColaboradorRepository (PDO MySQL 8.4)
├── Middleware/     # AdminApiKeyMiddleware, CorsMiddleware, JsonErrorMiddleware, RateLimitMiddleware
├── Validation/     # DepartamentoValidator, ColaboradorValidator
├── Exception/      # ApiException (400, 401, 404, 409, 422)
├── OpenApi/        # OpenApiSpec
└── Support/        # JsonResponse e StandaloneRuntime (container PSR-7 autônomo)
```

