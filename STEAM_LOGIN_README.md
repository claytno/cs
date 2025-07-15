# Login com Steam - Instruções de Configuração

## Configuração

### 1. Obter Chave da API Steam

1. Acesse https://steamcommunity.com/dev/apikey
2. Faça login com sua conta Steam
3. Digite um nome para seu domínio (ex: localhost)
4. Copie a chave gerada

### 2. Configurar variável de ambiente

No arquivo `.env.local`, substitua `YOUR_STEAM_API_KEY_HERE` pela sua chave real:

```
STEAM_API_KEY=sua_chave_aqui
```

### 3. Funcionalidades implementadas

- **Autenticação OpenID com Steam**: Usuários podem fazer login usando suas contas Steam
- **Dados do usuário Steam**: Nome, avatar, URL do perfil e Steam ID são salvos automaticamente
- **Provider customizado**: Suporte para busca por email ou Steam ID
- **Templates atualizados**: Botão de login Steam na página de login e exibição de dados Steam no perfil

### 4. Como funciona

1. O usuário clica em "Entrar com Steam" na página de login
2. É redirecionado para o Steam OpenID
3. Após autorizar, retorna para `/auth/steam/callback`
4. O sistema valida o OpenID e busca dados do usuário via API Steam
5. Se é a primeira vez, cria um novo usuário; caso contrário, atualiza os dados
6. O usuário é autenticado automaticamente

### 5. Estrutura do banco

Novos campos adicionados à tabela `user`:
- `steam_id`: ID único do Steam (nullable)
- `steam_name`: Nome do usuário no Steam (nullable)
- `steam_avatar`: URL do avatar no Steam (nullable)
- `steam_profile_url`: URL do perfil Steam (nullable)

### 6. Rotas disponíveis

- `/auth/steam`: Inicia o processo de login Steam
- `/auth/steam/callback`: Callback do OpenID Steam

### 7. Métodos úteis na entidade User

- `isSteamUser()`: Verifica se o usuário é do Steam
- `getDisplayName()`: Retorna o nome Steam ou email como nome de exibição

## Segurança

- Usuários Steam recebem um email temporário baseado no Steam ID
- A senha é definida como 'STEAM_AUTH' (placeholder)
- Usuários Steam são automaticamente verificados (`isVerified = true`)
- Todos recebem a role `ROLE_USER` por padrão
