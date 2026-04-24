/**
 * Vivero Inecolara - Main JavaScript
 * Vanilla JS functionality for the public site and dashboard
 */

// ============================================
// MOBILE MENU
// ============================================

function toggleMobileMenu() {
  const mobileMenu = document.getElementById('mobileMenu');
  if (mobileMenu) {
    mobileMenu.classList.toggle('active');
    document.body.style.overflow = mobileMenu.classList.contains('active') ? 'hidden' : '';
  }
}

// Close mobile menu when clicking outside
document.addEventListener('click', function(e) {
  const mobileMenu = document.getElementById('mobileMenu');
  const mobileMenuContent = document.querySelector('.mobile-menu-content');
  const menuBtn = document.querySelector('.mobile-menu-btn');
  
  if (mobileMenu && mobileMenu.classList.contains('active')) {
    if (!mobileMenuContent.contains(e.target) && !menuBtn.contains(e.target)) {
      toggleMobileMenu();
    }
  }
});

// ============================================
// FEATURED PLANTS (Landing Page)
// ============================================

function loadFeaturedPlants() {
  const container = document.getElementById('featuredPlants');
  if (!container || typeof PLANTS_DATA === 'undefined') return;
  
  const featured = PLANTS_DATA.filter(p => p.featured).slice(0, 4);
  
  container.innerHTML = featured.map(plant => `
    <div class="plant-card" onclick="openPlantModal(${plant.id})">
      <div class="plant-card-image">
        <img src="${plant.image}" alt="${plant.name}" loading="lazy">
        <span class="plant-card-badge badge badge-success">${plant.type}</span>
      </div>
      <div class="plant-card-body">
        <h4>${plant.name}</h4>
        <p class="scientific-name">${plant.scientificName}</p>
        <div class="plant-card-footer">
          <span class="plant-price">Bs. ${plant.price.toLocaleString()}</span>
          <span class="badge badge-${plant.stock > 20 ? 'success' : plant.stock > 5 ? 'warning' : 'error'}">
            ${plant.stock} disponibles
          </span>
        </div>
      </div>
    </div>
  `).join('');
}

// ============================================
// CATALOG PAGE
// ============================================

let currentFilters = {
  search: '',
  type: 'all',
  care: 'all',
  availability: 'all'
};

function initCatalog() {
  const container = document.getElementById('catalogGrid');
  if (!container || typeof PLANTS_DATA === 'undefined') return;
  
  // Setup filter listeners
  const searchInput = document.getElementById('catalogSearch');
  if (searchInput) {
    searchInput.addEventListener('input', function(e) {
      currentFilters.search = e.target.value.toLowerCase();
      renderCatalog();
    });
  }
  
  renderCatalog();
}

function setFilter(filterType, value) {
  currentFilters[filterType] = value;
  
  // Update active state on filter buttons
  const buttons = document.querySelectorAll(`[data-filter="${filterType}"]`);
  buttons.forEach(btn => {
    btn.classList.toggle('active', btn.dataset.value === value);
  });
  
  renderCatalog();
}

function renderCatalog() {
  const container = document.getElementById('catalogGrid');
  if (!container || typeof PLANTS_DATA === 'undefined') return;
  
  let filtered = PLANTS_DATA.filter(plant => {
    // Search filter
    if (currentFilters.search) {
      const searchTerm = currentFilters.search;
      const matchesSearch = 
        plant.name.toLowerCase().includes(searchTerm) ||
        plant.scientificName.toLowerCase().includes(searchTerm) ||
        plant.description.toLowerCase().includes(searchTerm);
      if (!matchesSearch) return false;
    }
    
    // Type filter
    if (currentFilters.type !== 'all' && plant.type !== currentFilters.type) {
      return false;
    }
    
    // Care filter
    if (currentFilters.care !== 'all' && plant.careLevel !== currentFilters.care) {
      return false;
    }
    
    // Availability filter
    if (currentFilters.availability === 'available' && plant.stock === 0) {
      return false;
    }
    if (currentFilters.availability === 'low' && plant.stock > 10) {
      return false;
    }
    
    return true;
  });
  
  if (filtered.length === 0) {
    container.innerHTML = `
      <div class="text-center py-12" style="grid-column: 1 / -1;">
        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin: 0 auto; color: var(--text-muted); opacity: 0.5;">
          <circle cx="11" cy="11" r="8"></circle>
          <path d="m21 21-4.35-4.35"></path>
        </svg>
        <h3 style="margin-top: 1rem; color: var(--text-secondary);">No se encontraron plantas</h3>
        <p style="color: var(--text-muted);">Intenta con otros filtros de busqueda</p>
      </div>
    `;
    return;
  }
  
  container.innerHTML = filtered.map(plant => `
    <div class="plant-card" onclick="openPlantModal(${plant.id})">
      <div class="plant-card-image">
        <img src="${plant.image}" alt="${plant.name}" loading="lazy">
        <span class="plant-card-badge badge badge-${plant.type === 'Ornamental' ? 'primary' : 'success'}">${plant.type}</span>
      </div>
      <div class="plant-card-body">
        <h4>${plant.name}</h4>
        <p class="scientific-name">${plant.scientificName}</p>
        <div class="plant-card-footer">
          <span class="plant-price">Bs. ${plant.price.toLocaleString()}</span>
          <span class="badge badge-${plant.stock > 20 ? 'success' : plant.stock > 5 ? 'warning' : 'error'}">
            ${plant.stock} disp.
          </span>
        </div>
      </div>
    </div>
  `).join('');
}

// ============================================
// PLANT DETAIL MODAL
// ============================================

function openPlantModal(plantId) {
  const plant = PLANTS_DATA.find(p => p.id === plantId);
  if (!plant) return;
  
  const modal = document.getElementById('plantModal');
  if (!modal) return;
  
  const modalBody = modal.querySelector('.modal-body');
  modalBody.innerHTML = `
    <div class="modal-plant-image">
      <img src="${plant.image}" alt="${plant.name}">
    </div>
    <div class="modal-plant-info">
      <span class="badge badge-${plant.type === 'Ornamental' ? 'primary' : 'success'} mb-4">${plant.type}</span>
      <h2>${plant.name}</h2>
      <p class="scientific-name">${plant.scientificName}</p>
      <p class="description">${plant.description}</p>
      
      <div class="care-info-grid">
        <div class="care-info-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="5"></circle>
            <line x1="12" y1="1" x2="12" y2="3"></line>
            <line x1="12" y1="21" x2="12" y2="23"></line>
            <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
            <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
            <line x1="1" y1="12" x2="3" y2="12"></line>
            <line x1="21" y1="12" x2="23" y2="12"></line>
            <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
            <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
          </svg>
          <span class="label">Luz</span>
          <span class="value">${plant.light}</span>
        </div>
        <div class="care-info-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"></path>
          </svg>
          <span class="label">Riego</span>
          <span class="value">${plant.water}</span>
        </div>
        <div class="care-info-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M14 14.76V3.5a2.5 2.5 0 0 0-5 0v11.26a4.5 4.5 0 1 0 5 0z"></path>
          </svg>
          <span class="label">Temperatura</span>
          <span class="value">${plant.temperature}</span>
        </div>
        <div class="care-info-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
            <line x1="16" y1="2" x2="16" y2="6"></line>
            <line x1="8" y1="2" x2="8" y2="6"></line>
            <line x1="3" y1="10" x2="21" y2="10"></line>
          </svg>
          <span class="label">Cuidado</span>
          <span class="value">${plant.careLevel}</span>
        </div>
      </div>
      
      <div class="flex justify-between items-center mt-6 pt-6" style="border-top: 1px solid var(--color-gray-200);">
        <div>
          <p class="text-muted mb-1" style="font-size: 0.875rem;">Precio por unidad</p>
          <p style="font-size: 1.5rem; font-weight: 700; color: var(--color-secondary); margin: 0;">
            Bs. ${plant.price.toLocaleString()}
          </p>
        </div>
        <div class="flex gap-2">
          <span class="badge badge-${plant.stock > 20 ? 'success' : plant.stock > 5 ? 'warning' : 'error'}">
            ${plant.stock} disponibles
          </span>
        </div>
      </div>
    </div>
  `;
  
  modal.classList.add('active');
  document.body.style.overflow = 'hidden';
}

function closePlantModal() {
  const modal = document.getElementById('plantModal');
  if (modal) {
    modal.classList.remove('active');
    document.body.style.overflow = '';
  }
}

// Close modal when clicking overlay
document.addEventListener('click', function(e) {
  if (e.target.classList.contains('modal-overlay')) {
    closePlantModal();
  }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    closePlantModal();
    toggleMobileMenu();
  }
});

// ============================================
// DASHBOARD SIDEBAR
// ============================================

function toggleDashboardSidebar() {
  const sidebar = document.getElementById('dashboardSidebar');
  if (sidebar) {
    sidebar.classList.toggle('mobile-open');
  }
}

// ============================================
// DASHBOARD INVENTORY
// ============================================

let inventoryFilters = {
  search: '',
  status: 'all'
};

function initInventory() {
  const container = document.getElementById('inventoryTable');
  if (!container || typeof LOTS_DATA === 'undefined') return;
  
  const searchInput = document.getElementById('inventorySearch');
  if (searchInput) {
    searchInput.addEventListener('input', function(e) {
      inventoryFilters.search = e.target.value.toLowerCase();
      renderInventory();
    });
  }
  
  renderInventory();
}

function setInventoryFilter(status) {
  inventoryFilters.status = status;
  
  const buttons = document.querySelectorAll('.inventory-status-filter');
  buttons.forEach(btn => {
    btn.classList.toggle('active', btn.dataset.status === status);
  });
  
  renderInventory();
}

function renderInventory() {
  const tbody = document.getElementById('inventoryTableBody');
  if (!tbody || typeof LOTS_DATA === 'undefined') return;
  
  let filtered = LOTS_DATA.filter(lot => {
    if (inventoryFilters.search) {
      const matchesSearch = 
        lot.species.toLowerCase().includes(inventoryFilters.search) ||
        lot.code.toLowerCase().includes(inventoryFilters.search);
      if (!matchesSearch) return false;
    }
    
    if (inventoryFilters.status !== 'all' && lot.status !== inventoryFilters.status) {
      return false;
    }
    
    return true;
  });
  
  tbody.innerHTML = filtered.map(lot => `
    <tr class="${lot.quantity < 20 ? 'table-low-stock' : ''}">
      <td><strong>${lot.code}</strong></td>
      <td>${lot.species}</td>
      <td>
        <div class="stock-indicator">
          <span class="stock-dot ${lot.quantity < 20 ? 'low' : lot.quantity < 50 ? 'medium' : 'high'}"></span>
          ${lot.quantity}
        </div>
      </td>
      <td>${lot.location}</td>
      <td><span class="badge badge-${getStatusBadge(lot.status)}">${lot.status}</span></td>
      <td>${lot.plantingDate}</td>
      <td>
        <div class="flex gap-2">
          <button class="btn btn-ghost btn-sm" onclick="editLot('${lot.code}')" title="Editar">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
              <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
            </svg>
          </button>
          <button class="btn btn-ghost btn-sm" onclick="viewLotHistory('${lot.code}')" title="Historial">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"></circle>
              <polyline points="12 6 12 12 16 14"></polyline>
            </svg>
          </button>
        </div>
      </td>
    </tr>
  `).join('');
}

function getStatusBadge(status) {
  const badges = {
    'Activo': 'success',
    'Germinacion': 'info',
    'Trasplante': 'warning',
    'Cuarentena': 'error'
  };
  return badges[status] || 'primary';
}

function editLot(code) {
  alert(`Editar lote: ${code}`);
}

function viewLotHistory(code) {
  alert(`Ver historial del lote: ${code}`);
}

// ============================================
// POS / SALES
// ============================================

let cart = [];
let selectedPaymentMethod = 'efectivo';

function initPOS() {
  renderPOSProducts();
  renderCart();
}

function renderPOSProducts() {
  const container = document.getElementById('posProductGrid');
  if (!container || typeof PLANTS_DATA === 'undefined') return;
  
  const availablePlants = PLANTS_DATA.filter(p => p.stock > 0);
  
  container.innerHTML = availablePlants.map(plant => `
    <div class="pos-product-card ${cart.some(item => item.id === plant.id) ? 'selected' : ''}" 
         onclick="addToCart(${plant.id})">
      <div class="pos-product-image">
        <img src="${plant.image}" alt="${plant.name}" loading="lazy">
      </div>
      <div class="pos-product-name">${plant.name}</div>
      <div class="pos-product-stock">${plant.stock} disponibles</div>
      <div class="pos-product-price">Bs. ${plant.price.toLocaleString()}</div>
    </div>
  `).join('');
}

function addToCart(plantId) {
  const plant = PLANTS_DATA.find(p => p.id === plantId);
  if (!plant) return;
  
  const existingItem = cart.find(item => item.id === plantId);
  
  if (existingItem) {
    if (existingItem.quantity < plant.stock) {
      existingItem.quantity++;
    }
  } else {
    cart.push({
      id: plant.id,
      name: plant.name,
      price: plant.price,
      image: plant.image,
      quantity: 1,
      maxStock: plant.stock
    });
  }
  
  renderCart();
  renderPOSProducts();
}

function removeFromCart(plantId) {
  cart = cart.filter(item => item.id !== plantId);
  renderCart();
  renderPOSProducts();
}

function updateCartQuantity(plantId, delta) {
  const item = cart.find(i => i.id === plantId);
  if (!item) return;
  
  item.quantity += delta;
  
  if (item.quantity <= 0) {
    removeFromCart(plantId);
    return;
  }
  
  if (item.quantity > item.maxStock) {
    item.quantity = item.maxStock;
  }
  
  renderCart();
}

function renderCart() {
  const container = document.getElementById('cartItems');
  const summaryContainer = document.getElementById('cartSummary');
  
  if (!container) return;
  
  if (cart.length === 0) {
    container.innerHTML = `
      <div class="pos-cart-empty">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <circle cx="9" cy="21" r="1"></circle>
          <circle cx="20" cy="21" r="1"></circle>
          <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
        </svg>
        <p>El carrito esta vacio</p>
        <p style="font-size: 0.75rem;">Selecciona productos para agregar</p>
      </div>
    `;
    if (summaryContainer) {
      summaryContainer.innerHTML = '';
    }
    return;
  }
  
  container.innerHTML = cart.map(item => `
    <div class="pos-cart-item">
      <div class="pos-cart-item-image">
        <img src="${item.image}" alt="${item.name}">
      </div>
      <div class="pos-cart-item-info">
        <div class="pos-cart-item-name">${item.name}</div>
        <div class="pos-cart-item-price">Bs. ${item.price.toLocaleString()} c/u</div>
      </div>
      <div class="pos-cart-item-qty">
        <button class="qty-btn" onclick="updateCartQuantity(${item.id}, -1)">-</button>
        <span class="qty-value">${item.quantity}</span>
        <button class="qty-btn" onclick="updateCartQuantity(${item.id}, 1)">+</button>
      </div>
      <div class="pos-cart-item-subtotal">Bs. ${(item.price * item.quantity).toLocaleString()}</div>
      <button class="pos-cart-item-remove" onclick="removeFromCart(${item.id})">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
    </div>
  `).join('');
  
  // Calculate totals
  const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
  const iva = subtotal * 0.16;
  const total = subtotal + iva;
  
  if (summaryContainer) {
    summaryContainer.innerHTML = `
      <div class="pos-cart-row">
        <span>Subtotal</span>
        <span>Bs. ${subtotal.toLocaleString()}</span>
      </div>
      <div class="pos-cart-row">
        <span>IVA (16%)</span>
        <span>Bs. ${iva.toLocaleString()}</span>
      </div>
      <div class="pos-cart-row total">
        <span>Total</span>
        <span>Bs. ${total.toLocaleString()}</span>
      </div>
    `;
  }
}

function selectPaymentMethod(method) {
  selectedPaymentMethod = method;
  
  const buttons = document.querySelectorAll('.payment-method-btn');
  buttons.forEach(btn => {
    btn.classList.toggle('selected', btn.dataset.method === method);
  });
}

function processSale() {
  if (cart.length === 0) {
    alert('El carrito esta vacio');
    return;
  }
  
  const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
  const total = subtotal * 1.16;
  
  const confirmation = confirm(
    `Confirmar venta:\n\n` +
    `Total: Bs. ${total.toLocaleString()}\n` +
    `Metodo de pago: ${selectedPaymentMethod}\n` +
    `Articulos: ${cart.reduce((sum, item) => sum + item.quantity, 0)}\n\n` +
    `¿Procesar venta?`
  );
  
  if (confirmation) {
    alert('Venta procesada exitosamente');
    cart = [];
    renderCart();
    renderPOSProducts();
  }
}

function clearCart() {
  if (cart.length === 0) return;
  
  if (confirm('¿Cancelar la venta actual?')) {
    cart = [];
    renderCart();
    renderPOSProducts();
  }
}

// ============================================
// AI ASSISTANT
// ============================================

const aiResponses = {
  'stock': 'Actualmente tenemos 15,234 plantas en stock, distribuidas en 48 lotes activos. Las especies con mayor disponibilidad son: Heliconia Caribaea (450 unidades), Palma Areca (380 unidades), y Croton Variegado (320 unidades).',
  'ventas': 'Las ventas de esta semana suman Bs. 2,450,000, un incremento del 12% respecto a la semana anterior. Los productos mas vendidos son plantas ornamentales para jardines residenciales.',
  'bajo stock': 'Hay 5 especies con stock critico: Orquidea Cattleya (8 unidades), Helecho Boston (12 unidades), Palma Kentia (15 unidades), Rosa del Desierto (18 unidades), y Planta de Jade (20 unidades).',
  'proyectos': 'Hay 3 proyectos institucionales activos: Reforestacion Parque del Este (entrega: 15/02), Jardineria Alcaldia (entrega: 28/02), y Escuelas Verdes (entrega: 10/03). Total comprometido: 2,500 plantas.',
  'cuadrillas': 'Las cuadrillas tienen 12 tareas pendientes para hoy: 5 de riego, 4 de trasplante, y 3 de fumigacion. El equipo A esta asignado al sector de ornamentales y el equipo B al sector forestal.'
};

let aiMessages = [
  { role: 'assistant', content: '¡Hola! Soy tu asistente virtual del vivero. Puedo ayudarte con informacion sobre inventario, ventas, lotes, proyectos y mas. ¿En que te puedo ayudar hoy?' }
];

function initAIAssistant() {
  renderAIMessages();
}

function renderAIMessages() {
  const container = document.getElementById('aiMessages');
  if (!container) return;
  
  container.innerHTML = aiMessages.map(msg => `
    <div class="ai-message ${msg.role}">
      ${msg.content}
    </div>
  `).join('');
  
  container.scrollTop = container.scrollHeight;
}

function sendAIMessage(message) {
  if (!message || message.trim() === '') return;
  
  // Add user message
  aiMessages.push({ role: 'user', content: message });
  renderAIMessages();
  
  // Clear input
  const input = document.getElementById('aiInput');
  if (input) input.value = '';
  
  // Simulate AI response
  setTimeout(() => {
    let response = 'Lo siento, no tengo informacion especifica sobre eso. Puedo ayudarte con consultas sobre stock, ventas, lotes con bajo stock, proyectos activos o tareas de cuadrillas.';
    
    const lowerMessage = message.toLowerCase();
    
    if (lowerMessage.includes('stock') || lowerMessage.includes('inventario') || lowerMessage.includes('plantas')) {
      response = aiResponses['stock'];
    } else if (lowerMessage.includes('venta') || lowerMessage.includes('vendido')) {
      response = aiResponses['ventas'];
    } else if (lowerMessage.includes('bajo') || lowerMessage.includes('critico') || lowerMessage.includes('falta')) {
      response = aiResponses['bajo stock'];
    } else if (lowerMessage.includes('proyecto') || lowerMessage.includes('institucional') || lowerMessage.includes('entrega')) {
      response = aiResponses['proyectos'];
    } else if (lowerMessage.includes('cuadrilla') || lowerMessage.includes('tarea') || lowerMessage.includes('equipo')) {
      response = aiResponses['cuadrillas'];
    }
    
    aiMessages.push({ role: 'assistant', content: response });
    renderAIMessages();
  }, 800);
}

function handleAIKeypress(event) {
  if (event.key === 'Enter') {
    const input = document.getElementById('aiInput');
    if (input) {
      sendAIMessage(input.value);
    }
  }
}

function askAIQuestion(question) {
  sendAIMessage(question);
}

// ============================================
// DASHBOARD ACTIVITY FEED
// ============================================

function loadDashboardData() {
  loadActivityFeed();
  loadLowStockAlerts();
}

function loadActivityFeed() {
  const container = document.getElementById('activityFeed');
  if (!container || typeof ACTIVITY_DATA === 'undefined') return;
  
  container.innerHTML = ACTIVITY_DATA.slice(0, 5).map(activity => `
    <div class="activity-item">
      <div class="activity-icon ${activity.type}">
        ${getActivityIcon(activity.type)}
      </div>
      <div class="activity-content">
        <p class="activity-text">${activity.text}</p>
        <span class="activity-time">${activity.time}</span>
      </div>
    </div>
  `).join('');
}

function getActivityIcon(type) {
  const icons = {
    'sale': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>',
    'inventory': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg>',
    'alert': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>'
  };
  return icons[type] || icons['inventory'];
}

function loadLowStockAlerts() {
  const container = document.getElementById('lowStockAlerts');
  if (!container || typeof PLANTS_DATA === 'undefined') return;
  
  const lowStock = PLANTS_DATA.filter(p => p.stock < 25).slice(0, 4);
  
  container.innerHTML = lowStock.map(plant => `
    <div class="low-stock-item">
      <div class="low-stock-info">
        <h5>${plant.name}</h5>
        <p>Lote: ${plant.lot || 'N/A'}</p>
      </div>
      <div class="low-stock-count">${plant.stock}</div>
    </div>
  `).join('');
}

// ============================================
// INITIALIZATION
// ============================================

document.addEventListener('DOMContentLoaded', function() {
  // Initialize based on current page
  const path = window.location.pathname;
  
  if (path.includes('catalogo')) {
    initCatalog();
  } else if (path.includes('dashboard')) {
    loadDashboardData();
  } else if (path.includes('inventario')) {
    initInventory();
  } else if (path.includes('ventas')) {
    initPOS();
  } else if (path.includes('asistente')) {
    initAIAssistant();
  }
});
