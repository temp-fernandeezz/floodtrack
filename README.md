# FloodTrack

Mapa colaborativo de alagamentos urbanos. Cruza notícias de veículos monitorados via RSS com reportes enviados pela própria comunidade, geocodifica as ocorrências e mostra tudo num mapa público em tempo (quase) real — colorido por nível de severidade (verde = baixo, amarelo = médio, vermelho = alto).

Projeto de TCC — Monitoramento de pontos de alagamento.

## O que o projeto faz

- **Mapa público** (`/`) com filtro por cidade, bairro e nível, mostrando só ocorrências ativas e aprovadas.
- **Scraper de notícias**: busca feeds RSS configuráveis, filtra por palavras-chave de alagamento (e descarta notícias de outro assunto, como acidentes/óbitos), geocodifica a localização e cria pontos automaticamente.
- **Reporte cidadão** (`/reportar`): formulário público com geolocalização do navegador e reCAPTCHA, para quem quiser reportar um alagamento diretamente.
- **Moderação**: notícias e reportes cidadãos entram como `pending` e passam por aprovação antes de aparecer no mapa público.
- **Expiração automática**: pontos ativos mais antigos que um limite configurável viram "resolvido" e somem do mapa (mas continuam disponíveis para exportação/histórico).
- **Estatísticas** (`/estatisticas`): ranking de risco por cidade, distribuição por nível/UF e série temporal dos últimos 30 dias.
- **Exportação CSV**: histórico completo de ocorrências e ranking de risco.
- **Painel administrativo** (`/admin`, Filament): CRUD de pontos com preview do local exato no mapa, fila de moderação, indicadores de saúde do scraper e configuração de RSS/domínios permitidos.

## Stack

- Laravel 12 + Filament 5 (painel admin)
- SQLite (padrão de desenvolvimento)
- Leaflet.js (mapas) + Alpine.js + Tailwind CSS v4 (Vite)
- Integrações opcionais: Anthropic (extração de localização das notícias), OpenWeatherMap (chuva recente), Google reCAPTCHA v2 (anti-spam no reporte cidadão)

## Como rodar localmente

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
npm install
npm run build
```

Ou, com um único comando (`composer setup` já faz os passos acima, exceto o `touch` do sqlite):

```bash
composer setup
```

Para desenvolvimento (sobe servidor, worker de fila, logs e Vite juntos):

```bash
composer dev
```

### Variáveis de ambiente relevantes

Veja `.env.example` para a lista completa. As chaves abaixo são opcionais — sem elas, a respectiva feature fica automaticamente desativada:

| Variável | Para quê serve |
|---|---|
| `ANTHROPIC_API_KEY` | Extração de localização (cidade/bairro) a partir do texto da notícia |
| `OPENWEATHERMAP_API_KEY` | Widget de "chuva recente" no mapa |
| `GOOGLE_RECAPTCHA_SITE_KEY` / `GOOGLE_RECAPTCHA_SECRET_KEY` | Anti-spam no formulário de reporte cidadão |

### Agendamento

O scraper de notícias e a expiração automática rodam via scheduler do Laravel (`routes/console.php`):

- `flood:fetch-news` — a cada 10 minutos
- `flood:expire-points` — a cada hora

Em produção, garanta que o cron do Laravel esteja rodando (`* * * * * php artisan schedule:run`). Em desenvolvimento, ambos os comandos também podem ser disparados manualmente pelo Dashboard do painel admin ("Buscar notícias agora" / "Expirar pontos antigos agora").

## Painel administrativo

Acesse `/admin` com um usuário cadastrado na tabela `users`. Por lá dá pra:

- Gerenciar pontos de alagamento (criar, editar, aprovar/rejeitar, com preview de mapa)
- Acompanhar a fila de moderação e a saúde do scraper (última execução, itens importados)
- Configurar feeds RSS e domínios permitidos em **Configurações → Scraper e Regras**

## Testes

```bash
composer test
```
