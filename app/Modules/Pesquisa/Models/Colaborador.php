<?php

namespace App\Modules\Pesquisa\Models;

use App\Models\Empresa;
use App\Models\Filial;
use App\Models\User;
use App\Modules\Pesquisa\Database\Factories\ColaboradorFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;
use OpenApi\Attributes as OA;

/**
 * Colaborador da empresa — a pessoa física alvo do convite individual da
 * pesquisa, independente de possuir uma conta de acesso ao sistema
 * (`users`). CPF e data de nascimento são dados sensíveis sob a ótica da
 * LGPD: ficam gravados no banco SEMPRE cifrados (nunca em texto plano) e só
 * podem ser lidos em claro dentro da aplicação, por quem tiver a permissão
 * `colaborador.visualizar_dados_sensiveis` — toda leitura em claro é
 * automaticamente registrada pelo LogMiddleware do sistema (rota + usuário +
 * IP + timestamp), atendendo ao princípio de responsabilização da LGPD.
 * Fora isso, a exibição padrão (listagens, exports) usa sempre a versão
 * mascarada (`cpf_mascarado` / `data_nascimento_mascarada`).
 */
#[OA\Schema(
    schema: 'PesquisaColaborador',
    description: 'Colaborador da empresa (alvo do convite individual da pesquisa)',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'empresa_id', type: 'integer', example: 1),
        new OA\Property(property: 'nome', type: 'string', example: 'Maria da Silva'),
        new OA\Property(property: 'email', type: 'string', nullable: true),
        new OA\Property(property: 'cargo', type: 'string', nullable: true),
        new OA\Property(property: 'cpf_mascarado', type: 'string', nullable: true, example: '***.***.**9-09'),
        new OA\Property(property: 'ativo', type: 'boolean', example: true),
        new OA\Property(property: 'origem', type: 'string', enum: ['manual', 'importacao_csv']),
    ]
)]
class Colaborador extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pesq_colaboradores';

    protected $fillable = [
        'empresa_id',
        'filial_id',
        'setor_id',
        'user_id',
        'matricula',
        'nome',
        'email',
        'cargo',
        'ativo',
        'cpf',
        'data_nascimento',
        'origem',
        'importado_por',
        'base_legal_lgpd',
        'consentimento_em',
    ];

    protected $hidden = [
        'cpf',
        'cpf_hash',
        'data_nascimento',
    ];

    protected $appends = [
        'cpf_mascarado',
        'data_nascimento_mascarada',
    ];

    protected $casts = [
        'ativo'            => 'boolean',
        'consentimento_em' => 'datetime',
    ];

    protected static function newFactory(): ColaboradorFactory
    {
        return ColaboradorFactory::new();
    }

    // ── Relationships ────────────────────────────────────────────────────

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function filial(): BelongsTo
    {
        return $this->belongsTo(Filial::class, 'filial_id');
    }

    public function setor(): BelongsTo
    {
        return $this->belongsTo(Setor::class, 'setor_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function importadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'importado_por');
    }

    // ── Dados sensíveis: cifrados no banco, mascarados por padrão ───────────

    /**
     * CPF em claro — só acessível dentro da aplicação (nunca serializado
     * automaticamente: está em $hidden). Normaliza para dígitos ao gravar e
     * atualiza cpf_hash (usado só para detectar duplicidade, nunca reversível).
     */
    protected function cpf(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? Crypt::decryptString($value) : null,
            set: function (?string $value) {
                $digitos = $value ? preg_replace('/\D/', '', $value) : null;

                return [
                    'cpf'      => $digitos ? Crypt::encryptString($digitos) : null,
                    'cpf_hash' => $digitos ? hash('sha256', $digitos) : null,
                ];
            },
        );
    }

    protected function dataNascimento(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? Crypt::decryptString($value) : null,
            set: fn (?string $value) => $value ? Crypt::encryptString($value) : null,
        );
    }

    protected function cpfMascarado(): Attribute
    {
        return Attribute::make(get: function () {
            $cpf = $this->cpf;
            if (! $cpf) {
                return null;
            }

            $digitos = str_pad($cpf, 11, '0', STR_PAD_LEFT);

            return sprintf('***.***.**%s-%s', substr($digitos, 8, 1), substr($digitos, 9, 2));
        });
    }

    protected function dataNascimentoMascarada(): Attribute
    {
        return Attribute::make(get: function () {
            $data = $this->data_nascimento;

            return $data ? '**/**/'.substr($data, 0, 4) : null;
        });
    }

    /**
     * Únicos pontos de leitura do dado sensível em claro — usados apenas
     * pelo endpoint explícito de revelação (permissão dedicada + log
     * automático da rota, nunca em listagens).
     */
    public function dadosSensiveisEmClaro(): array
    {
        return [
            'cpf'             => $this->cpf,
            'data_nascimento' => $this->data_nascimento,
        ];
    }
}
