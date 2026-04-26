<?php
/**
 * Vista: Asistente IA
 * Variables esperadas: $user (array), $historial (array opcional)
 */
?>
<div class="assistant-layout">
    <!-- Chat Panel -->
    <div class="assistant-chat">
        <div class="chat-messages" id="chatMessages">
            <!-- Welcome Message -->
            <div class="chat-message assistant-message">
                <div class="message-avatar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2a10 10 0 1 0 10 10 4 4 0 0 1-5-5 4 4 0 0 1-5-5"></path>
                        <path d="M8.5 8.5v.01"></path>
                        <path d="M16 15.5v.01"></path>
                        <path d="M12 12v.01"></path>
                    </svg>
                </div>
                <div class="message-content">
                    <div class="message-header">
                        <span class="message-sender">Asistente IA</span>
                        <span class="message-time">Ahora</span>
                    </div>
                    <div class="message-text">
                        <p>Hola <?= htmlspecialchars($user['nombre'] ?? 'Usuario') ?>! Soy el asistente virtual del Vivero INECOLARA. Estoy aquí para ayudarte con:</p>
                        <ul>
                            <li>Consultas sobre inventario y stock</li>
                            <li>Información de ventas y estadísticas</li>
                            <li>Estado de lotes y producción</li>
                            <li>Recomendaciones de cuidado de plantas</li>
                            <li>Generación de reportes</li>
                        </ul>
                        <p>¿En qué puedo ayudarte hoy?</p>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($historial)): ?>
                <?php foreach ($historial as $mensaje): ?>
                <div class="chat-message <?= $mensaje['tipo'] === 'user' ? 'user-message' : 'assistant-message' ?>">
                    <div class="message-avatar">
                        <?php if ($mensaje['tipo'] === 'user'): ?>
                            <?= strtoupper(substr($user['nombre'] ?? 'U', 0, 1)) ?>
                        <?php else: ?>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 2a10 10 0 1 0 10 10 4 4 0 0 1-5-5 4 4 0 0 1-5-5"></path>
                            </svg>
                        <?php endif; ?>
                    </div>
                    <div class="message-content">
                        <div class="message-header">
                            <span class="message-sender"><?= $mensaje['tipo'] === 'user' ? htmlspecialchars($user['nombre'] ?? 'Tú') : 'Asistente IA' ?></span>
                            <span class="message-time"><?= htmlspecialchars($mensaje['hora'] ?? '') ?></span>
                        </div>
                        <div class="message-text">
                            <?= $mensaje['contenido'] ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Chat Input -->
        <div class="chat-input-container">
            <form class="chat-form" id="chatForm">
                <div class="chat-input-wrapper">
                    <textarea 
                        class="chat-input" 
                        id="chatInput" 
                        placeholder="Escribe tu mensaje o pregunta..."
                        rows="1"
                    ></textarea>
                    <button type="submit" class="chat-submit" id="chatSubmit">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="22" y1="2" x2="11" y2="13"></line>
                            <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                        </svg>
                    </button>
                </div>
                <p class="chat-hint">Presiona Enter para enviar, Shift+Enter para nueva línea</p>
            </form>
        </div>
    </div>
    
    <!-- Sidebar -->
    <div class="assistant-sidebar">
        <!-- Suggestions -->
        <div class="assistant-card">
            <h4 class="assistant-card-title">Preguntas Sugeridas</h4>
            <div class="suggestions-list">
                <button class="suggestion-btn" data-question="¿Cuál es el inventario actual del vivero?">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                    </svg>
                    ¿Cuál es el inventario actual?
                </button>
                <button class="suggestion-btn" data-question="¿Cuáles fueron las ventas de esta semana?">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="20" x2="18" y2="10"></line>
                        <line x1="12" y1="20" x2="12" y2="4"></line>
                        <line x1="6" y1="20" x2="6" y2="14"></line>
                    </svg>
                    ¿Ventas de esta semana?
                </button>
                <button class="suggestion-btn" data-question="¿Qué plantas tienen stock bajo?">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                    ¿Plantas con stock bajo?
                </button>
                <button class="suggestion-btn" data-question="¿Cuáles son las plantas más vendidas este mes?">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                        <polyline points="17 6 23 6 23 12"></polyline>
                    </svg>
                    ¿Plantas más vendidas?
                </button>
                <button class="suggestion-btn" data-question="¿Qué lotes necesitan riego hoy?">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"></path>
                    </svg>
                    ¿Lotes que necesitan riego?
                </button>
                <button class="suggestion-btn" data-question="Genera un reporte de inventario en PDF">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                    </svg>
                    Generar reporte PDF
                </button>
            </div>
        </div>
        
        <!-- Recent Topics -->
        <div class="assistant-card">
            <h4 class="assistant-card-title">Temas Recientes</h4>
            <ul class="recent-topics" id="recentTopics">
                <li class="topic-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    <span>Consulta de inventario</span>
                    <span class="topic-time">Hace 2h</span>
                </li>
                <li class="topic-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    <span>Reporte de ventas</span>
                    <span class="topic-time">Ayer</span>
                </li>
            </ul>
        </div>
        
        <!-- Quick Stats -->
        <div class="assistant-card">
            <h4 class="assistant-card-title">Datos Rápidos</h4>
            <div class="quick-stats">
                <div class="quick-stat">
                    <span class="quick-stat-value">12,450</span>
                    <span class="quick-stat-label">Plantas en Stock</span>
                </div>
                <div class="quick-stat">
                    <span class="quick-stat-value">45</span>
                    <span class="quick-stat-label">Ventas Hoy</span>
                </div>
                <div class="quick-stat">
                    <span class="quick-stat-value">8</span>
                    <span class="quick-stat-label">Alertas Activas</span>
                </div>
            </div>
        </div>
    </div>
</div>
