<?php
/**
 * Vista: Punto de Venta (POS)
 * Variables esperadas: $productos (array), $clientes (array), $user (array)
 */
?>
<div class="pos-layout">
    <!-- Products Panel -->
    <div class="pos-products">
        <div class="pos-products-header">
            <div class="search-box">
                <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                </svg>
                <input type="text" class="search-input" placeholder="Buscar producto por nombre o código..." id="productSearch">
            </div>
            
            <div class="pos-categories">
                <button class="category-btn active" data-category="todos">Todos</button>
                <button class="category-btn" data-category="ornamental">Ornamentales</button>
                <button class="category-btn" data-category="frutal">Frutales</button>
                <button class="category-btn" data-category="forestal">Forestales</button>
                <button class="category-btn" data-category="palma">Palmas</button>
            </div>
        </div>
        
        <div class="pos-products-grid" id="productsGrid">
            <!-- Se llena dinámicamente con JavaScript -->
        </div>
    </div>
    
    <!-- Cart Panel -->
    <div class="pos-cart">
        <div class="pos-cart-header">
            <h3 class="pos-cart-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="9" cy="21" r="1"></circle>
                    <circle cx="20" cy="21" r="1"></circle>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                </svg>
                Carrito de Venta
            </h3>
            <button class="btn btn-ghost btn-sm" id="clearCart">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="3 6 5 6 21 6"></polyline>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                </svg>
                Vaciar
            </button>
        </div>
        
        <div class="pos-cart-items" id="cartItems">
            <!-- Empty state -->
            <div class="cart-empty" id="cartEmpty">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="9" cy="21" r="1"></circle>
                    <circle cx="20" cy="21" r="1"></circle>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                </svg>
                <p>El carrito está vacío</p>
                <span>Selecciona productos para agregar</span>
            </div>
            
            <!-- Cart items list -->
            <ul class="cart-list hidden" id="cartList">
                <!-- Se llena dinámicamente -->
            </ul>
        </div>
        
        <!-- Client Selection -->
        <div class="pos-client">
            <label class="form-label">Cliente</label>
            <div class="client-select-wrapper">
                <select class="form-select" id="clientSelect">
                    <option value="">Seleccionar cliente...</option>
                    <option value="publico">Público General</option>
                    <?php if (!empty($clientes)): ?>
                        <?php foreach ($clientes as $cliente): ?>
                        <option value="<?= htmlspecialchars($cliente['id']) ?>">
                            <?= htmlspecialchars($cliente['nombre']) ?> - <?= htmlspecialchars($cliente['documento'] ?? 'S/D') ?>
                        </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <button class="btn btn-ghost btn-sm" id="addClientBtn" title="Agregar nuevo cliente">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="8.5" cy="7" r="4"></circle>
                        <line x1="20" y1="8" x2="20" y2="14"></line>
                        <line x1="23" y1="11" x2="17" y2="11"></line>
                    </svg>
                </button>
            </div>
        </div>
        
        <!-- Cart Summary -->
        <div class="pos-summary">
            <div class="summary-row">
                <span>Subtotal</span>
                <span id="subtotal">Bs. 0.00</span>
            </div>
            <div class="summary-row">
                <span>IVA (16%)</span>
                <span id="iva">Bs. 0.00</span>
            </div>
            <div class="summary-row summary-total">
                <span>Total</span>
                <span id="total">Bs. 0.00</span>
            </div>
        </div>
        
        <!-- Payment Methods -->
        <div class="pos-payment">
            <label class="form-label">Método de Pago</label>
            <div class="payment-methods">
                <button class="payment-btn active" data-method="efectivo">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                        <line x1="1" y1="10" x2="23" y2="10"></line>
                    </svg>
                    Efectivo
                </button>
                <button class="payment-btn" data-method="transferencia">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="1" x2="12" y2="23"></line>
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                    </svg>
                    Transferencia
                </button>
                <button class="payment-btn" data-method="pago-movil">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect>
                        <line x1="12" y1="18" x2="12.01" y2="18"></line>
                    </svg>
                    Pago Móvil
                </button>
                <button class="payment-btn" data-method="punto">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                        <line x1="1" y1="10" x2="23" y2="10"></line>
                    </svg>
                    Punto
                </button>
            </div>
        </div>
        
        <!-- Actions -->
        <div class="pos-actions">
            <button class="btn btn-outline btn-lg" id="holdSale">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                Apartar
            </button>
            <button class="btn btn-primary btn-lg" id="completeSale" disabled>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                Completar Venta
            </button>
        </div>
    </div>
</div>

<!-- Sale Complete Modal -->
<div class="modal" id="saleCompleteModal">
    <div class="modal-backdrop" id="saleCompleteBackdrop"></div>
    <div class="modal-content modal-sm">
        <div class="modal-body text-center">
            <div class="success-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
            </div>
            <h3 class="modal-title">Venta Completada</h3>
            <p class="sale-number">Venta #<span id="saleNumber">0000</span></p>
            <p class="sale-total">Total: <span id="saleTotal">Bs. 0.00</span></p>
            
            <div class="modal-actions">
                <button class="btn btn-outline" id="printReceipt">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 9 6 2 18 2 18 9"></polyline>
                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                        <rect x="6" y="14" width="12" height="8"></rect>
                    </svg>
                    Imprimir
                </button>
                <button class="btn btn-primary" id="newSale">Nueva Venta</button>
            </div>
        </div>
    </div>
</div>
