<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Sistema Pesquisas — API Multi-Tenant',
    description: 'Backend REST API multiempresa com RBAC. Use POST /api/v1/auth/login para obter o JWT e clique em Authorize inserindo: Bearer {token}. Super Admins podem usar o header X-Empresa-Id para escopar requisições a uma empresa.',
    contact: new OA\Contact(email: 'admin@sistema.com'),
    license: new OA\License(name: 'MIT')
)]
#[OA\Server(url: L5_SWAGGER_CONST_HOST, description: 'Servidor Local XAMPP')]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
    description: 'Token JWT obtido via POST /api/v1/auth/login. Insira: Bearer {token}'
)]
#[OA\Tag(name: 'Autenticação', description: 'Login, logout, refresh e gestão de senha')]
#[OA\Tag(name: 'Dashboard', description: 'Painéis de controle Super Admin e Empresa')]
#[OA\Tag(name: 'Empresas', description: 'Gestão de empresas (multi-tenant)')]
#[OA\Tag(name: 'Filiais', description: 'Gestão de filiais por empresa')]
#[OA\Tag(name: 'Usuários', description: 'Gestão de usuários por empresa')]
#[OA\Tag(name: 'Roles', description: 'Perfis de acesso (RBAC)')]
#[OA\Tag(name: 'Permissões', description: 'Permissões do sistema')]
#[OA\Tag(name: 'Configurações', description: 'Configurações por empresa/filial')]
#[OA\Tag(name: 'Logs', description: 'Auditoria e rastreabilidade')]
#[OA\Tag(name: 'Perfil', description: 'Gestão do próprio perfil do usuário autenticado')]
#[OA\Get(
    path: '/api/health',
    summary: 'Health check',
    description: 'Verifica se a API está online.',
    tags: ['Autenticação'],
    responses: [
        new OA\Response(
            response: 200,
            description: 'API online',
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'status', type: 'string', example: 'ok'),
                new OA\Property(property: 'timestamp', type: 'string', format: 'date-time'),
            ])
        ),
    ]
)]
#[OA\Schema(
    schema: 'RespostaErro',
    description: 'Resposta de erro padrão da API',
    properties: [
        new OA\Property(property: 'sucesso', type: 'boolean', example: false),
        new OA\Property(property: 'mensagem', type: 'string', example: 'Mensagem descritiva do erro.'),
    ]
)]
#[OA\Schema(
    schema: 'Configuracao',
    description: 'Configuração chave-valor por empresa/filial',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'empresa_id', type: 'integer', nullable: true),
        new OA\Property(property: 'filial_id', type: 'integer', nullable: true),
        new OA\Property(property: 'chave', type: 'string', example: 'smtp.host'),
        new OA\Property(property: 'valor', type: 'string', nullable: true, example: 'smtp.gmail.com'),
        new OA\Property(property: 'tipo', type: 'string', enum: ['string', 'boolean', 'integer', 'json'], example: 'string'),
        new OA\Property(property: 'grupo', type: 'string', nullable: true, example: 'email'),
    ]
)]
class SwaggerInfo
{
}
