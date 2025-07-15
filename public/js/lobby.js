class LobbySystem {
    constructor() {
        this.refreshInterval = null;
        this.isRefreshing = false;
        this.tooltip = null;
        this.init();
    }

    init() {
        this.bindEvents();
        this.startAutoRefresh();
        this.addTooltips();
    }

    bindEvents() {
        console.log('bindEvents called');
        
        // Criar lobby
        const createLobbyBtn = document.getElementById('createLobbyBtn');
        console.log('createLobbyBtn found:', !!createLobbyBtn);
        if (createLobbyBtn) {
            console.log('Adding click listener to createLobbyBtn');
            createLobbyBtn.addEventListener('click', () => {
                console.log('createLobbyBtn clicked!');
                this.showCreateModal();
            });
        }

        // Sair da lobby
        const leaveLobbyBtn = document.getElementById('leaveLobbyBtn');
        if (leaveLobbyBtn) {
            leaveLobbyBtn.addEventListener('click', () => this.leaveLobby());
        }

        // Refresh manual
        const refreshBtn = document.getElementById('refreshBtn');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => this.refreshLobbies());
        }

        // Escape para fechar modals
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeModals();
            }
        });

        // Click fora do modal para fechar
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal-backdrop')) {
                this.closeModals();
            }
        });
    }

    addTooltips() {
        // Adicionar tooltips nos avatares
        const avatars = document.querySelectorAll('.lobby-avatar');
        avatars.forEach(avatar => {
            avatar.addEventListener('mouseenter', (e) => {
                const tooltip = e.target.getAttribute('data-tooltip');
                if (tooltip) {
                    this.showTooltip(e.target, tooltip);
                }
            });
            avatar.addEventListener('mouseleave', () => {
                this.hideTooltip();
            });
        });
    }

    showTooltip(element, text) {
        this.hideTooltip();
        
        this.tooltip = document.createElement('div');
        this.tooltip.className = 'absolute bg-gray-900 text-white px-2 py-1 rounded text-sm z-50 pointer-events-none';
        this.tooltip.textContent = text;
        
        document.body.appendChild(this.tooltip);
        
        const rect = element.getBoundingClientRect();
        this.tooltip.style.left = rect.left + (rect.width / 2) - (this.tooltip.offsetWidth / 2) + 'px';
        this.tooltip.style.top = rect.top - this.tooltip.offsetHeight - 8 + 'px';
    }

    hideTooltip() {
        if (this.tooltip) {
            this.tooltip.remove();
            this.tooltip = null;
        }
    }

    showCreateModal() {
        console.log('showCreateModal called');
        const modal = document.getElementById('createLobbyModal');
        const input = modal.querySelector('input[name="lobbyName"]');
        const charCount = document.getElementById('charCount');
        
        console.log('modal found:', !!modal);
        console.log('input found:', !!input);
        console.log('charCount found:', !!charCount);
        
        if (modal && input) {
            modal.classList.remove('hidden');
            input.focus();
            input.select();
            
            // Atualizar contador de caracteres
            const updateCharCount = () => {
                const count = input.value.length;
                charCount.textContent = `${count}/50`;
                charCount.className = count > 40 ? 'text-xs text-orange-400' : 'text-xs text-gray-500';
            };
            
            updateCharCount();
            
            // Remover event listeners antigos para evitar duplicação
            input.removeEventListener('input', input._updateCharCount);
            input.removeEventListener('keypress', input._handleKeyPress);
            
            input._updateCharCount = updateCharCount;
            input._handleKeyPress = (e) => {
                if (e.key === 'Enter') {
                    this.createLobby();
                } else if (e.key === 'Escape') {
                    this.closeModals();
                }
            };
            
            input.addEventListener('input', input._updateCharCount);
            input.addEventListener('keypress', input._handleKeyPress);
            
            // Fechar modal clicando fora
            modal.onclick = (e) => {
                if (e.target === modal) {
                    this.closeModals();
                }
            };
        }
    }

    closeModals() {
        const modals = document.querySelectorAll('[id$="Modal"]');
        modals.forEach(modal => {
            modal.classList.add('hidden');
        });
    }

    async createLobby() {
        const modal = document.getElementById('createLobbyModal');
        const input = modal.querySelector('input[name="lobbyName"]');
        const createBtn = modal.querySelector('button[onclick="lobbySystem.createLobby()"]');
        const lobbyName = input.value.trim();

        if (!lobbyName) {
            this.showNotification('Nome da lobby é obrigatório!', 'error');
            input.focus();
            return;
        }

        // Mostrar loading
        const originalText = createBtn.innerHTML;
        createBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Criando...';
        createBtn.disabled = true;

        try {
            const response = await fetch('/lobby/create', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ name: lobbyName })
            });

            const data = await response.json();

            if (data.success) {
                this.showNotification('🎉 Lobby criada com sucesso!', 'success');
                this.closeModals();
                input.value = '';
                await this.refreshLobbies();
            } else {
                this.showNotification(data.message, 'error');
                input.focus();
            }
        } catch (error) {
            this.showNotification('Erro ao criar lobby!', 'error');
            console.error('Erro:', error);
        } finally {
            // Restaurar botão
            createBtn.innerHTML = originalText;
            createBtn.disabled = false;
        }
    }

    async joinLobby(lobbyId) {
        try {
            const response = await fetch(`/lobby/join/${lobbyId}`, {
                method: 'POST'
            });

            const data = await response.json();

            if (data.success) {
                this.showNotification('Você entrou na lobby!', 'success');
                await this.refreshLobbies();
            } else {
                this.showNotification(data.message, 'error');
            }
        } catch (error) {
            this.showNotification('Erro ao entrar na lobby!', 'error');
            console.error('Erro:', error);
        }
    }

    async leaveLobby() {
        if (!confirm('Tem certeza que deseja sair da lobby?')) {
            return;
        }

        try {
            const response = await fetch('/lobby/leave', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            });

            const data = await response.json();

            if (data.success) {
                this.showNotification('Você saiu da lobby!', 'success');
                await this.refreshLobbies();
            } else {
                this.showNotification(data.message, 'error');
            }
        } catch (error) {
            this.showNotification('Erro ao sair da lobby!', 'error');
            console.error('Erro:', error);
        }
    }

    async refreshLobbies() {
        if (this.isRefreshing) return;
        
        this.isRefreshing = true;
        const refreshBtn = document.getElementById('refreshBtn');
        if (refreshBtn) {
            refreshBtn.classList.add('animate-spin');
        }

        try {
            const response = await fetch('/lobby/refresh');
            const data = await response.json();

            this.updateUserLobby(data.userLobby);
            this.updateAvailableLobbies(data.availableLobbies, data.isInFullLobby, data.isLeader);
        } catch (error) {
            this.showNotification('Erro ao atualizar lobbies!', 'error');
            console.error('Erro:', error);
        } finally {
            this.isRefreshing = false;
            if (refreshBtn) {
                refreshBtn.classList.remove('animate-spin');
            }
        }
    }

    updateUserLobby(userLobby) {
        const container = document.getElementById('userLobbyContainer');
        if (!container) return;

        if (userLobby) {
            let playersHtml = '';
            for (let i = 0; i < 5; i++) {
                const player = userLobby.players[i];
                if (player) {
                    playersHtml += `
                        <div class="player-slot text-center">
                            <div class="relative">
                                <img src="${player.steamAvatar || '/images/default-avatar.svg'}" 
                                     alt="${this.escapeHtml(player.displayName)}" 
                                     class="w-20 h-20 rounded-full border-4 ${player.isLeader ? 'border-yellow-500' : 'border-blue-500'} mx-auto">
                                ${player.isLeader ? '<div class="leader-crown"><i class="fas fa-crown text-white"></i></div>' : ''}
                            </div>
                            <p class="text-sm text-white mt-2 font-semibold">${this.escapeHtml(player.displayName)}</p>
                            <p class="text-xs text-gray-400">${player.isLeader ? 'Líder' : 'Jogador'}</p>
                        </div>
                    `;
                } else {
                    playersHtml += `
                        <div class="player-slot text-center">
                            <div class="empty-slot">
                                <div class="w-20 h-20 bg-gray-700 rounded-full border-4 border-gray-600 flex items-center justify-center mx-auto">
                                    <i class="fas fa-user text-gray-500 text-2xl"></i>
                                </div>
                                <p class="text-xs text-gray-500 mt-2">Aguardando...</p>
                            </div>
                        </div>
                    `;
                }
            }

            container.innerHTML = `
                <div class="bg-gray-800 border border-gray-700 rounded-lg p-8 shadow-lg">
                    <div class="text-center mb-8">
                        <h2 class="text-3xl font-bold text-white mb-2">${this.escapeHtml(userLobby.name)}</h2>
                        <p class="text-gray-400">
                            ${userLobby.isFull ? 'Lobby completa - Pronto para desafiar!' : `Aguardando jogadores (${userLobby.playerCount}/5)`}
                        </p>
                    </div>
                    
                    <div class="flex justify-center items-center space-x-8 mb-8">
                        ${playersHtml}
                    </div>
                    
                    <div class="text-center">
                        ${userLobby.isFull && userLobby.isLeader ? '<p class="text-green-400 mb-4"><i class="fas fa-check-circle mr-2"></i>Lobby completa! Você pode desafiar outras lobbies.</p>' : ''}
                        
                        <button id="leaveLobbyBtn" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-lg transition-colors mr-4">
                            <i class="fas fa-sign-out-alt mr-2"></i>
                            ${userLobby.isLeader ? 'Fechar Lobby' : 'Sair da Lobby'}
                        </button>
                        
                        ${!userLobby.isFull ? '<button onclick="navigator.clipboard.writeText(window.location.href)" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition-colors"><i class="fas fa-link mr-2"></i>Copiar Link</button>' : ''}
                    </div>
                </div>
            `;
            
            // Rebind events
            const leaveLobbyBtn = document.getElementById('leaveLobbyBtn');
            if (leaveLobbyBtn) {
                leaveLobbyBtn.addEventListener('click', () => this.leaveLobby());
            }
        } else {
            // Mostrar grid de lobbies quando não está em nenhuma
            window.location.reload();
        }
        
        // Readd tooltips
        this.addTooltips();
    }

    updateAvailableLobbies(lobbies, isInFullLobby = false, isLeader = false) {
        // Primeira busca por sidebar, depois por container geral
        let container = document.getElementById('sidebarLobbies');
        let isMainGrid = false;
        
        if (!container) {
            // Se não encontrou sidebar, procura pelo grid principal
            container = document.querySelector('.grid.grid-cols-1.sm\\:grid-cols-2');
            isMainGrid = true;
        }
        
        if (!container) return;

        if (isMainGrid) {
            // Grid view principal (quando não está em lobby) - recarrega para atualizar template
            if (lobbies.length === 0) {
                container.innerHTML = `
                    <div class="col-span-full text-center py-12">
                        <i class="fas fa-search text-6xl text-gray-600 mb-4"></i>
                        <h3 class="text-xl font-bold text-gray-400 mb-2">Nenhuma Lobby Disponível</h3>
                        <p class="text-gray-500">Seja o primeiro a criar uma lobby!</p>
                    </div>
                `;
            } else {
                // Recarrega para mostrar as lobbies atualizadas com o template Twig
                window.location.reload();
            }
        } else {
            // Sidebar view (quando está em lobby)
            if (isInFullLobby && isLeader) {
                // Só mostra lobbies se for líder de lobby completa
                if (lobbies.length === 0) {
                    container.innerHTML = `
                        <div class="text-center py-8">
                            <i class="fas fa-trophy text-4xl text-gray-600 mb-4"></i>
                            <p class="text-gray-400 text-sm">Nenhuma lobby completa disponível para desafiar</p>
                        </div>
                    `;
                } else {
                    container.innerHTML = `
                        <div class="space-y-3">
                            ${lobbies.map(lobby => `
                                <div class="lobby-card rounded-lg p-3 cursor-pointer challenge-mode" 
                                     onclick="lobbySystem.challengeLobby(${lobby.id})">
                                    <div class="flex items-center justify-between mb-2">
                                        <div class="flex items-center gap-2">
                                            <div class="brazil-flag"></div>
                                            <div class="level-circle" style="width: 24px; height: 24px; font-size: 12px;">
                                                ${Math.floor(Math.random() * 8) + 8}
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <span class="bg-blue-600 text-white px-2 py-1 rounded text-xs">5/5</span>
                                            <div class="text-red-400 mt-1"><i class="fas fa-sword text-xs"></i></div>
                                        </div>
                                    </div>
                                    <h4 class="font-semibold text-white text-sm mb-1">${this.escapeHtml(lobby.name)}</h4>
                                    <p class="text-xs text-gray-400">${this.escapeHtml(lobby.leader?.displayName || 'Unknown')}</p>
                                    
                                    <div class="flex gap-1 mt-2">
                                        ${lobby.players?.slice(0, 3).map(player => `
                                            <img src="${player.user?.steamAvatar || '/images/default-avatar.svg'}" 
                                                 alt="Player" 
                                                 class="w-6 h-6 rounded-full border border-gray-500">
                                        `).join('') || ''}
                                        ${lobby.playerCount > 3 ? `
                                            <div class="w-6 h-6 bg-gray-600 rounded-full border border-gray-500 flex items-center justify-center">
                                                <span class="text-xs text-white">+${lobby.playerCount - 3}</span>
                                            </div>
                                        ` : ''}
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    `;
                }
            } else {
                // Não é líder ou lobby não está completa
                container.innerHTML = `
                    <div class="text-center py-8">
                        <i class="fas fa-users text-4xl text-gray-600 mb-4"></i>
                        <p class="text-gray-400 text-sm text-center leading-relaxed">
                            ${!isInFullLobby ? 
                                'Complete sua lobby com 5 jogadores para poder desafiar outras lobbies' : 
                                'Apenas o líder pode desafiar outras lobbies'
                            }
                        </p>
                    </div>
                `;
            }
        }
    }

    startAutoRefresh() {
        this.refreshInterval = setInterval(() => {
            this.refreshLobbies();
        }, 5000); // Refresh a cada 5 segundos
    }

    stopAutoRefresh() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
            this.refreshInterval = null;
        }
    }

    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 transition-all transform translate-x-full`;
        
        const colors = {
            success: 'bg-green-600 text-white',
            error: 'bg-red-600 text-white',
            info: 'bg-blue-600 text-white',
            warning: 'bg-yellow-600 text-black'
        };
        
        notification.className += ` ${colors[type] || colors.info}`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        // Animar entrada
        setTimeout(() => {
            notification.classList.remove('translate-x-full');
        }, 100);
        
        // Remover após 3 segundos
        setTimeout(() => {
            notification.classList.add('translate-x-full');
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 3000);
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    async challengeLobby(lobbyId) {
        // Delegar para o MatchSystem
        if (window.matchSystem) {
            await window.matchSystem.challengeLobby(lobbyId);
        }
    }

    destroy() {
        this.stopAutoRefresh();
        this.hideTooltip();
    }
}

// Sistema de Matches - Desafios e Vetos
class MatchSystem {
    constructor() {
        this.pollInterval = null;
        this.init();
    }

    init() {
        this.startMatchPolling();
    }

    async challengeLobby(lobbyId) {
        try {
            const response = await fetch(`/match/challenge/${lobbyId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
            });

            const data = await response.json();
            
            if (data.success) {
                this.showNotification(data.message, 'success');
                // Atualizar a interface
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                this.showNotification(data.error, 'error');
            }
        } catch (error) {
            this.showNotification('Erro ao enviar desafio!', 'error');
            console.error('Erro:', error);
        }
    }

    async acceptChallenge(matchId) {
        try {
            const response = await fetch(`/match/accept/${matchId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
            });

            const data = await response.json();
            
            if (data.success) {
                this.showNotification(data.message, 'success');
                // Redirecionar para o veto de mapas
                setTimeout(() => {
                    window.location.href = '/match/veto';
                }, 1500);
            } else {
                this.showNotification(data.error, 'error');
            }
        } catch (error) {
            this.showNotification('Erro ao aceitar desafio!', 'error');
            console.error('Erro:', error);
        }
    }

    async declineChallenge(matchId) {
        try {
            const response = await fetch(`/match/decline/${matchId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
            });

            const data = await response.json();
            
            if (data.success) {
                this.showNotification(data.message, 'success');
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                this.showNotification(data.error, 'error');
            }
        } catch (error) {
            this.showNotification('Erro ao recusar desafio!', 'error');
            console.error('Erro:', error);
        }
    }

    async startMatchPolling() {
        // Verificar status da partida a cada 3 segundos
        this.pollInterval = setInterval(async () => {
            try {
                const response = await fetch('/match/status');
                const data = await response.json();
                
                if (data.stage) {
                    // Se há uma partida ativa, verificar se precisa redirecionar
                    const currentPath = window.location.pathname;
                    
                    switch (data.stage) {
                        case 'veto':
                            if (currentPath !== '/match/veto') {
                                window.location.href = '/match/veto';
                            }
                            break;
                        case 'connect':
                            if (currentPath !== '/match/connect') {
                                window.location.href = '/match/connect';
                            }
                            break;
                    }
                }
            } catch (error) {
                console.log('Erro ao verificar status da partida:', error);
            }
        }, 3000);
    }

    showNotification(message, type = 'info') {
        // Usar o mesmo sistema de notificação do LobbySystem
        if (window.lobbySystem) {
            window.lobbySystem.showNotification(message, type);
        } else {
            alert(message);
        }
    }

    destroy() {
        if (this.pollInterval) {
            clearInterval(this.pollInterval);
        }
    }
}

// Inicializar quando a página carregar
let lobbySystem;
let matchSystem;
document.addEventListener('DOMContentLoaded', () => {
    console.log('Creating LobbySystem instance');
    lobbySystem = new LobbySystem();
    matchSystem = new MatchSystem();
    window.lobbySystem = lobbySystem; // Tornar global para debug
    window.matchSystem = matchSystem; // Tornar global para debug
    console.log('LobbySystem created:', !!lobbySystem);
    console.log('MatchSystem created:', !!matchSystem);
});

// Cleanup ao sair da página
window.addEventListener('beforeunload', () => {
    if (lobbySystem) {
        lobbySystem.destroy();
    }
    if (matchSystem) {
        matchSystem.destroy();
    }
});
