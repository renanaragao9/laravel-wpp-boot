# Laravel WPP Boot

Bot de atendimento para WhatsApp. Recebe mensagens por **webhook** de três provedores (**WhatsApp Cloud API da Meta**, **Evolution API** ou **Twilio**), conduz o contato por um **menu em árvore** e, quando ele escolhe um setor, abre uma **demanda** para a equipe responder pelo painel.

- **Stack:** Laravel 13, PHP 8.3+, Filament 5 (Livewire 4), fila no banco de dados.
- **Painel:** `/admin` (a rota `/` redireciona para o login).
- **Desenho:** baseado no módulo de WhatsApp do Cronos (webhook da Evolution, menu, conversas e demandas), agora com vários provedores.

## Sumário

1. [Como funciona](#como-funciona)
2. [Instalação](#instalação)
3. [Configuração dos provedores](#configuração-dos-provedores)
4. [Como usar o painel](#como-usar-o-painel)
5. [Como o bot conversa](#como-o-bot-conversa)
6. [Arquitetura](#arquitetura)
7. [Banco de dados](#banco-de-dados)
8. [Adicionar um novo provedor](#adicionar-um-novo-provedor)
9. [Testes](#testes)
10. [Problemas comuns](#problemas-comuns)

---

## Como funciona

```
WhatsApp ──► Meta | Evolution | Twilio ──► POST /api/webhooks/{provedor}
                                              │  valida assinatura/token
                                              │  grava o log (payload cru) e descarta duplicados
                                              ▼
                                     fila: ProcessIncomingMessageJob
                                              │
                                     NavigateMenuService (máquina de estados)
                                       ├─ responde o menu pelo MESMO provedor
                                       └─ ao escolher um setor: abre a Demanda
                                              ▼
                              Painel: a equipe responde e conclui a demanda
```

1. O provedor chama o webhook. O código **autentica** a chamada (veja a seção de provedores), grava um **log** com o payload e enfileira o processamento. O webhook responde na hora (a Meta reenvia se demorar).
2. O job leva a mensagem à **máquina de estados** do menu.
3. Enquanto o contato navega, o bot responde com o menu. Ao escolher uma opção do tipo **Setor**, abre-se uma **demanda** e o bot **para de responder** esse contato: as mensagens dele passam a ser gravadas na demanda.
4. A equipe responde pelo painel (a resposta sai pelo provedor de onde a conversa veio). Ao **concluir** a demanda, o contato volta a falar com o bot.

## Instalação

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
```

`migrate --seed` cria as tabelas, o **admin** (a partir de `APP_ADMIN_*`) e um **menu de atendimento completo** de exemplo (veja [Menu de exemplo](#menu-de-exemplo-seeder)).

> O `.env.example` usa SQLite. Se trocar para MySQL/PostgreSQL, lembre que `migrate:fresh` **apaga todas as tabelas** do banco configurado.

Para rodar:

```bash
php artisan serve        # painel em http://localhost:8000/admin
php artisan queue:work   # processa as mensagens recebidas (obrigatório)
```

Sem o `queue:work`, os webhooks são aceitos e gravados no log com status **Recebido**, mas o bot não responde.

### Variáveis gerais

| Variável | Para que serve |
|---|---|
| `APP_URL` | URL pública do sistema. Importante para o Twilio (a assinatura inclui a URL). |
| `APP_ADMIN_NAME` / `APP_ADMIN_MAIL` / `APP_ADMIN_PASSWORD` | Admin criado pelo seeder. **Troque a senha fora do ambiente local.** |
| `WHATSAPP_SESSION_TIMEOUT_MINUTES` | Inatividade (em minutos) após a qual a navegação volta ao menu inicial. `0` desliga. Padrão: 30. |
| `WHATSAPP_DEFAULT_DRIVER` | Provedor padrão (`meta`, `evolution` ou `twilio`), reservado para envios sem conversa de origem. Hoje todo envio usa o provedor da própria conversa. |

## Configuração dos provedores

Cada provedor tem uma URL de webhook própria. **Todos rejeitam (403) a chamada se o segredo correspondente não estiver configurado**: não existe modo "aberto".

| Provedor | URL do webhook |
|---|---|
| Meta (Cloud API) | `GET e POST  {APP_URL}/api/webhooks/meta` |
| Evolution API | `POST  {APP_URL}/api/webhooks/evolution` |
| Twilio | `POST  {APP_URL}/api/webhooks/twilio` |

> Para testar localmente, exponha a porta com um túnel (ngrok, cloudflared) e use a URL pública. Os provedores exigem **HTTPS** público.

### Meta: WhatsApp Cloud API

1. No app da Meta (WhatsApp → Configuration), em **Webhook**, informe a URL `/api/webhooks/meta` e um **Verify token** qualquer que você escolher.
2. Assine o campo **messages**.
3. Configure no `.env`:

```
META_WHATSAPP_VERIFY_TOKEN=o-mesmo-token-do-passo-1
META_WHATSAPP_APP_SECRET=app-secret-do-app        # valida a assinatura X-Hub-Signature-256
META_WHATSAPP_ACCESS_TOKEN=token-de-acesso        # para enviar respostas
META_WHATSAPP_PHONE_NUMBER_ID=id-do-numero
META_WHATSAPP_GRAPH_VERSION=v21.0
```

- O **GET** de verificação devolve o `hub.challenge` só se o verify token bater.
- O **POST** só é aceito com assinatura `X-Hub-Signature-256` válida (HMAC-SHA256 do corpo com o App Secret).
- Entendidas: mensagens de texto, respostas de lista/botão interativo e botões de template. Notificações de status (entregue/lido) são registradas como **Ignorado**.
- A Meta só permite responder com texto livre dentro de 24 h da última mensagem do contato.

### Evolution API

1. Na instância, configure o webhook para `{APP_URL}/api/webhooks/evolution`, evento **MESSAGES_UPSERT**, enviando o cabeçalho `apikey: <seu token>` (ou, se não der para enviar cabeçalho, `?token=<seu token>` na URL).
2. Configure no `.env`:

```
EVOLUTION_WEBHOOK_TOKEN=token-que-voce-escolheu   # valida o webhook recebido
EVOLUTION_API_BASE_URL=https://sua-evolution.exemplo.com
EVOLUTION_API_KEY=apikey-da-evolution             # para enviar respostas
EVOLUTION_API_INSTANCE=nome-da-instancia
```

- Mensagens enviadas por você (`fromMe`), de grupos (`@g.us`) e outros eventos são ignorados.

### Twilio: WhatsApp

1. No console do Twilio (sandbox ou remetente WhatsApp), em **When a message comes in**, informe `{APP_URL}/api/webhooks/twilio`, método **POST**.
2. Configure no `.env`:

```
TWILIO_ACCOUNT_SID=ACxxxxxxxx
TWILIO_AUTH_TOKEN=token                  # valida X-Twilio-Signature e envia respostas
TWILIO_WHATSAPP_FROM=whatsapp:+14155238886
```

- A assinatura (`X-Twilio-Signature`) é calculada sobre a **URL completa** + parâmetros. Se o app estiver atrás de proxy ou túnel, a URL vista pelo Laravel precisa ser a mesma que está no Twilio (configure `APP_URL` e os proxies confiáveis).
- O webhook responde com um TwiML vazio; as respostas do bot saem pela API do Twilio.

### Idempotência

Os provedores reenviam webhooks. Uma mensagem com o mesmo ID (`message_id`) do mesmo provedor só é processada **uma vez**; as repetidas são descartadas e contadas na resposta (`duplicated`).

## Como usar o painel

Entre em `/admin` com o admin do `.env`.

### Bot → Menu do bot

Aqui você monta a árvore de opções. Cada linha é uma **opção**:

- **Dentro de:** o menu pai. Vazio = **menu inicial** (raiz). Só um menu inicial ativo é usado.
- **Título:** nome mostrado ao contato.
- **Número da opção:** o que o contato digita (obrigatório quando há menu pai).
- **Tipo:** **Menu** (abre outro menu) ou **Setor (abre demanda)**; neste caso informe o nome do **Setor**.
- **Mensagem:** texto mostrado antes das opções daquele menu.
- **Ativo:** opções inativas não aparecem.

O painel ajuda a montar o menu (ideias do front do Cronos):

- **Árvore do menu:** no topo da lista, mostra o menu como o contato o navega: indentado, com ícone de pasta (menu) e de bandeira (setor), selo com o nome do setor e selo **inativo**. O botão **+** de cada menu abre "Nova opção" já **dentro** dele; o lápis edita.
- **Um só menu principal:** a primeira opção criada é a raiz. Depois disso, "Dentro de" passa a ser obrigatório e o campo avisa isso.
- **"Dentro de" só lista menus**, nunca setores, nunca a própria opção nem as opções que estão abaixo dela (evita ciclos). Por padrão vem preenchido com o menu principal, ou com o menu em que você clicou no **+**.
- **Número da opção:** obrigatório dentro de um menu, no máximo 10 caracteres e **único por menu** (o mesmo número pode existir em menus diferentes, e números de opções removidas são liberados).
- **Número sugerido:** ao criar uma opção, o campo já vem com o próximo número livre do menu (o maior número atual + 1, ou 1 no menu vazio; opções removidas e números não numéricos são ignorados). Ao trocar o menu em "Dentro de", a sugestão acompanha, sem sobrescrever um número que você já digitou. Na edição nada é alterado.
- **Setor:** o nome do setor é obrigatório quando o tipo é "Setor".
- **Títulos contextuais:** "Criar menu principal" ou `Nova opção em "Suporte"`.
- **Remoção protegida:** uma opção que tem itens dentro não pode ser removida (nem em lote); remova ou mova os itens antes.

### Menu de exemplo (seeder)

`php artisan migrate --seed` carrega o menu de atendimento completo de `database/seeders/MenuNodeSeeder.php`, que serve de ponto de partida para editar no painel:

```
Menu inicial  ("Olá! 👋 Bem-vindo ao nosso atendimento...")
├── 1 Comercial
│   ├── 1 Quero contratar ou comprar        → setor Comercial
│   ├── 2 Orçamentos e propostas            → setor Comercial
│   └── 3 Falar com um consultor            → setor Comercial
├── 2 Suporte
│   ├── 1 Estou com um problema
│   │   ├── 1 No sistema ou aplicativo      → setor Suporte técnico
│   │   ├── 2 Acesso e senha                → setor Suporte técnico
│   │   └── 3 Outro problema                → setor Suporte técnico
│   ├── 2 Dúvidas de uso                    → setor Suporte técnico
│   └── 3 Acompanhar um chamado             → setor Suporte técnico
├── 3 Financeiro
│   ├── 1 Segunda via de boleto             → setor Financeiro
│   ├── 2 Pagamentos e comprovantes         → setor Financeiro
│   └── 3 Cancelamento e reembolso          → setor Financeiro
├── 4 Recursos Humanos
│   ├── 1 Quero trabalhar conosco           → setor Recursos Humanos
│   └── 2 Sou colaborador                   → setor Recursos Humanos
└── 5 Falar com um atendente                → setor Atendimento geral
```

- Toda opção do tipo **Setor** abre uma demanda com o título `Setor - Opção` (ex.: `Financeiro - Segunda via de boleto`).
- O seeder **só roda se não existir nenhuma opção**: nunca sobrescreve um menu já configurado. Para recarregar o exemplo, remova as opções existentes e rode `php artisan db:seed --class=MenuNodeSeeder`.
- Os testes garantem que o menu é consistente (uma só raiz, nenhum menu sem opções, números sem repetição, setores sem filhos) e que **todo setor é alcançável** digitando os números do caminho.

### Atendimento → Demandas

Lista as demandas abertas pelo bot, com filtros por status, setor, provedor, atendente e data.

- **Responder:** envia uma mensagem ao contato pelo mesmo provedor da conversa, grava na demanda e, se estava **Aberta**, passa para **Em andamento** atribuindo você como atendente.
- **Concluir:** encerra a demanda e a conversa; na próxima mensagem o contato volta ao menu do bot.
- **Ver / Editar:** a página da demanda mostra o histórico de **mensagens**; a edição permite mudar status, atendente e observações internas.
- Demandas não são criadas à mão: nascem do bot.

### Bot → Logs de webhook

Registro de tudo que chegou: provedor, telefone, texto, **status** (Recebido, Processado, Ignorado, Falhou) e o **erro**, quando houver. Abra um log para ver o **payload cru** do provedor (a `apikey` nunca é gravada). Somente leitura.

### Administração → Usuários

Somente para o **super admin** (`is_master`). Crie os atendentes aqui; qualquer usuário entra no painel, mas só o super admin gerencia usuários.

## Como o bot conversa

Regras do `NavigateMenuService`:

| Situação | O que o bot faz |
|---|---|
| Primeira mensagem (ou sem conversa ativa) | Mostra o menu inicial. |
| Contato digita o número de uma opção **Menu** | Mostra o submenu. |
| Contato digita o número de uma opção **Setor** | Abre a demanda, avisa o contato e passa a conversa para a equipe. |
| Número inexistente | Responde "Opção inválida" e repete o menu. |
| `0`, `menu`, `início` ou `inicio` | Volta ao menu inicial. |
| Ficou parado mais que `WHATSAPP_SESSION_TIMEOUT_MINUTES` | Reinicia no menu inicial. |
| Há demanda ativa (aberta, em andamento, aguardando ou pausada) | **Não responde**; só grava a mensagem na demanda. |
| Demanda concluída | Volta a responder com o menu. |
| Nenhum menu ativo configurado | Não envia nada. |

As respostas saem pelo **mesmo provedor** de onde a mensagem chegou. Falhas de envio são registradas no log da aplicação e não derrubam o processamento.

## Arquitetura

```
app/
├── Contracts/WhatsAppProvider.php          Interface comum dos provedores
├── Enums/                                  WhatsAppDriver, ConversationStatus, DemandStatus,
│                                           MenuNodeType, MessageDirection, WebhookLogStatus
├── Http/Controllers/Api/WhatsAppWebhookController.php   Controller fino (verify + handle)
├── Jobs/ProcessIncomingMessageJob.php      Processa a mensagem em fila
├── Services/
│   ├── Webhook/   HandleWebhookService, VerifyWebhookService
│   ├── WhatsApp/  ProviderManager, SendMessageService, Drivers/{Meta,Evolution,Twilio}Provider
│   ├── Menu/      NavigateMenuService (máquina de estados do bot), BuildMenuTreeService (árvore do painel)
│   └── Demand/    ReplyDemandService, CloseDemandService
├── Support/WhatsApp/                       IncomingMessage, SendResult, Phone (DTOs e helper)
├── Models/                                 WhatsApp{WebhookLog,MenuNode,Conversation,Demand,DemandMessage}
└── Filament/
    ├── Resources/    MenuNodes, Demands, WebhookLogs, Users  (Resource + Pages + Schemas + Tables)
    ├── Actions/      ReplyDemandAction, CloseDemandAction, DeleteMenuNodeAction (+ em lote)
    └── Filters/      Filtros por model (Common, Demand, WebhookLog, MenuNode, User)
config/whatsapp.php                         Credenciais e opções dos provedores
routes/api.php                              /api/webhooks/{meta|evolution|twilio}
```

Cada provedor implementa `WhatsAppProvider`:

| Método | Função |
|---|---|
| `authorize(Request)` | Valida assinatura/token do webhook. |
| `parse(Request)` | Converte o payload do provedor em `IncomingMessage` (telefone só dígitos, texto, opção escolhida, id). |
| `sendText(phone, text)` | Envia a resposta pela API do provedor e devolve um `SendResult`. |

O resto do sistema (menu, demandas, painel) **não conhece** o provedor: só usa `IncomingMessage` e `SendMessageService`.

## Banco de dados

| Tabela | Para que serve |
|---|---|
| `whatsapp_webhook_logs` | Um registro por webhook recebido: `driver`, `event`, `phone`, `message_id`, `text`, `option`, `raw_payload` (JSON), `status`, `error`. |
| `whatsapp_menu_nodes` | Árvore do menu: `parent_id`, `label`, `option_number`, `type` (menu/sector), `sector`, `message`, `active`. |
| `whatsapp_conversations` | Estado por telefone: `driver`, `phone`, `status` (navegando, com atendente, finalizada), `current_node_id`, `last_interaction_at`. |
| `whatsapp_demands` | Demanda por setor: `driver`, `phone`, `sector`, `title`, `status`, `notes`, `position`, `assigned_user_id`. |
| `whatsapp_demand_messages` | Histórico da demanda: `direction` (recebida/enviada), `body`, `user_id`. |

Todas usam soft delete e seguem o padrão de tabelas do projeto.

## Adicionar um novo provedor

1. Adicione o caso em `app/Enums/WhatsAppDriver.php`.
2. Crie `app/Services/WhatsApp/Drivers/NovoProvider.php` implementando `WhatsAppProvider` (autorização, `parse` e `sendText`).
3. Registre-o no `match` de `ProviderManager`.
4. Adicione as credenciais em `config/whatsapp.php` e `.env.example`.
5. Inclua o nome do provedor no `whereIn('driver', [...])` de `routes/api.php`.
6. Escreva os testes seguindo `tests/Feature/Webhook/`.

## Testes

```bash
php artisan test
```

Rodam em SQLite em memória e com fila `sync`; as chamadas HTTP aos provedores são simuladas (`Http::fake`). Cobertura:

- **Webhooks** (`tests/Feature/Webhook`): verificação e assinatura da Meta, token da Evolution, assinatura do Twilio, rejeição sem segredo configurado, deduplicação, eventos ignorados e payload sem `apikey`.
- **Fluxo do bot** (`BotFlowTest`): navegação, abertura de demanda, silêncio com demanda ativa, opção inválida, reinício, timeout, resposta do atendente e conclusão.
- **Painel** (`AdminPanelTest`): páginas dos resources, ações Responder/Concluir, validação do formulário do menu e filtros.
- **Construtor do menu** (`MenuBuilderTest`): raiz única, padrão do pai, número único por menu, opções de pai, proteção de remoção e ordem da árvore.
- **Models** (`tests/Unit/Models`): CRUD com soft delete, casts e relações.

## Problemas comuns

| Sintoma | Causa e solução |
|---|---|
| Webhook responde 403 | Segredo ausente ou errado (`META_WHATSAPP_APP_SECRET`, `EVOLUTION_WEBHOOK_TOKEN` ou `TWILIO_AUTH_TOKEN`). No Twilio, confira também se a URL e o `APP_URL` são idênticos. |
| Meta não verifica o webhook | O verify token do `.env` precisa ser igual ao digitado no app da Meta; a URL precisa ser HTTPS público. |
| Logs ficam em **Recebido** e o bot não responde | O worker não está rodando: `php artisan queue:work`. |
| Log **Processado**, mas o contato não recebe a resposta | Credenciais de envio ausentes ou inválidas. Veja `storage/logs/laravel.log` (aviso "falha ao enviar mensagem"). |
| Log **Falhou** | Abra o log e leia o campo **Erro**. |
| Bot não responde a um contato específico | Ele tem uma demanda ativa: responda ou **conclua** a demanda. |
| Nada é enviado | Não há opção raiz ativa: crie um item sem "Dentro de" em **Menu do bot**. |
| Mudei o `.env` e nada mudou | `php artisan config:clear`. |
# laravel-wpp-boot
