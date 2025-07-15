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
        // Criar lobby
        const createLobbyBtn = document.getElementById('createLobbyBtn');
        if (createLobbyBtn) {
            createLobbyBtn.addEventListener('click', () => this.showCreateModal());
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
        const modal = document.getElementById('createLobbyModal');
        if (modal) {
            modal.classList.remove('hidden');
            const input = modal.querySelector('input[name="lobbyName"]');
            if (input) {
                input.focus();
            }
        }
    }

    closeModals() {
        const modals = document.querySelectorAll('.modal-backdrop');
        modals.forEach(modal => {
            modal.classList.add('hidden');
        });
    }

    async createLobby() {
        const modal = document.getElementById('createLobbyModal');
        const input = modal.querySelector('input[name="lobbyName"]');
        const lobbyName = input.value.trim();

        if (!lobbyName) {
            this.showNotification('Nome da lobby é obrigatório!', 'error');
            return;
        }

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
                this.showNotification('Lobby criada com sucesso!', 'success');
                this.closeModals();
                input.value = '';
                await this.refreshLobbies();
            } else {
                this.showNotification(data.message, 'error');
            }
        } catch (error) {
            this.showNotification('Erro ao criar lobby!', 'error');
            console.error('Erro:', error);
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
                method: 'POST'
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
            this.updateAvailableLobbies(data.availableLobbies);
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
            container.innerHTML = `
                <div class="bg-gray-800 border border-gray-700 rounded-lg p-6 shadow-lg">
                    <div class="text-center mb-6">
                        <h3 class="text-xl font-bold text-white mb-2">${this.escapeHtml(userLobby.name)}</h3>
                        <p class="text-gray-400">Aguardando jogadores...</p>
                    </div>
                    
                    <div class="flex justify-center items-center space-x-4 mb-6">
                        <!-- Slot vazio -->
                        <div class="flex flex-col items-center">
                            <div class="w-16 h-16 bg-gray-600 rounded-full border-2 border-gray-500 flex items-center justify-center">
                                <i class="fas fa-user text-gray-400"></i>
                            </div>
                            <span class="text-xs text-gray-500 mt-2">Aguardando</span>
                        </div>
                        
                        <!-- Slot vazio -->
                        <div class="flex flex-col items-center">
                            <div class="w-16 h-16 bg-gray-600 rounded-full border-2 border-gray-500 flex items-center justify-center">
                                <i class="fas fa-user text-gray-400"></i>
                            </div>
                            <span class="text-xs text-gray-500 mt-2">Aguardando</span>
                        </div>
                        
                        <!-- Criador (centro) -->
                        <div class="flex flex-col items-center">
                            <img src="${userLobby.creator.steamAvatar || '/images/default-avatar.png'}" 
                                 alt="Avatar" 
                                 class="w-20 h-20 rounded-full border-4 border-blue-500 lobby-avatar"
                                 data-tooltip="${this.escapeHtml(userLobby.creator.displayName)} (Líder)">
                            <span class="text-xs text-white mt-2 font-semibold">${this.escapeHtml(userLobby.creator.displayName)}</span>
                            <span class="text-xs text-blue-400">Líder</span>
                        </div>
                        
                        <!-- Slot vazio -->
                        <div class="flex flex-col items-center">
                            <div class="w-16 h-16 bg-gray-600 rounded-full border-2 border-gray-500 flex items-center justify-center">
                                <i class="fas fa-user text-gray-400"></i>
                            </div>
                            <span class="text-xs text-gray-500 mt-2">Aguardando</span>
                        </div>
                        
                        <!-- Slot vazio -->
                        <div class="flex flex-col items-center">
                            <div class="w-16 h-16 bg-gray-600 rounded-full border-2 border-gray-500 flex items-center justify-center">
                                <i class="fas fa-user text-gray-400"></i>
                            </div>
                            <span class="text-xs text-gray-500 mt-2">Aguardando</span>
                        </div>
                    </div>
                    
                    <div class="text-center">
                        <button id="leaveLobbyBtn" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-lg transition-colors">
                            <i class="fas fa-sign-out-alt mr-2"></i>Sair da Lobby
                        </button>
                    </div>
                </div>
            `;
            
            // Rebind events
            const leaveLobbyBtn = document.getElementById('leaveLobbyBtn');
            if (leaveLobbyBtn) {
                leaveLobbyBtn.addEventListener('click', () => this.leaveLobby());
            }
        } else {
            container.innerHTML = `
                <div class="bg-gray-800 border border-gray-700 rounded-lg p-8 text-center shadow-lg">
                    <div class="mb-6">
                        <i class="fas fa-users text-6xl text-gray-600 mb-4"></i>
                        <h3 class="text-xl font-bold text-white mb-2">Nenhuma Lobby Ativa</h3>
                        <p class="text-gray-400">Crie uma lobby para começar a jogar!</p>
                    </div>
                    <button id="createLobbyBtn" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg transition-colors font-semibold">
                        <i class="fas fa-plus mr-2"></i>Criar Lobby
                    </button>
                </div>
            `;
            
            // Rebind events
            const createLobbyBtn = document.getElementById('createLobbyBtn');
            if (createLobbyBtn) {
                createLobbyBtn.addEventListener('click', () => this.showCreateModal());
            }
        }
        
        // Readd tooltips
        this.addTooltips();
    }

    updateAvailableLobbies(lobbies) {
        const container = document.getElementById('availableLobbiesContainer');
        if (!container) return;

        if (lobbies.length === 0) {
            container.innerHTML = `
                <div class="bg-gray-800 border border-gray-700 rounded-lg p-6 text-center">
                    <i class="fas fa-search text-4xl text-gray-600 mb-4"></i>
                    <p class="text-gray-400">Nenhuma lobby disponível</p>
                </div>
            `;
            return;
        }

        container.innerHTML = lobbies.map(lobby => `
            <div class="bg-gray-800 border border-gray-700 rounded-lg p-4 lobby-card cursor-pointer hover:border-blue-500" 
                 onclick="lobbySystem.joinLobby(${lobby.id})">
                <div class="flex items-center space-x-3">
                    <img src="${lobby.creator.steamAvatar || '/images/default-avatar.png'}" 
                         alt="Avatar" 
                         class="w-12 h-12 rounded-full border-2 border-gray-600">
                    <div class="flex-1">
                        <h4 class="font-semibold text-white">${this.escapeHtml(lobby.name)}</h4>
                        <p class="text-sm text-gray-400">${this.escapeHtml(lobby.creator.displayName)}</p>
                    </div>
                    <div class="text-blue-400">
                        <i class="fas fa-play"></i>
                    </div>
                </div>
            </div>
        `).join('');
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

    destroy() {
        this.stopAutoRefresh();
        this.hideTooltip();
    }
}

// Inicializar quando a página carregar
let lobbySystem;
document.addEventListener('DOMContentLoaded', () => {
    lobbySystem = new LobbySystem();
});

// Cleanup ao sair da página
window.addEventListener('beforeunload', () => {
    if (lobbySystem) {
        lobbySystem.destroy();
    }
});
