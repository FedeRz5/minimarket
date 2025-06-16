// Funciones JavaScript para mejorar la UX - CON MOBILE MENU MEJORADO
document.addEventListener("DOMContentLoaded", () => {
  // Crear botón de menú móvil si no existe
  createMobileMenuButton()

  // Toggle sidebar en mobile (mantener compatibilidad)
  const toggleBtn = document.querySelector(".toggle-sidebar")
  const sidebar = document.querySelector(".sidebar")

  if (toggleBtn && sidebar) {
    toggleBtn.addEventListener("click", () => {
      sidebar.classList.toggle("show")
    })
  }

  // Inicializar otras funcionalidades
  initFormValidation()
  initAlerts()
  initAnimations()
})

// Crear botón de menú móvil
function createMobileMenuButton() {
  // Solo crear si no existe
  if (document.querySelector(".mobile-menu-btn")) {
    // Si ya existe, configurar eventos
    setupMobileMenuEvents()
    return
  }

  const mobileBtn = document.createElement("button")
  mobileBtn.className = "mobile-menu-btn"
  mobileBtn.id = "mobileMenuBtn"
  mobileBtn.innerHTML = '<i class="fas fa-bars"></i>'
  mobileBtn.setAttribute("aria-label", "Abrir menú")

  // Crear overlay
  const overlay = document.createElement("div")
  overlay.className = "mobile-overlay"
  overlay.id = "mobileOverlay"

  document.body.appendChild(mobileBtn)
  document.body.appendChild(overlay)

  setupMobileMenuEvents()
}

function setupMobileMenuEvents() {
  const mobileBtn = document.querySelector(".mobile-menu-btn")
  const overlay = document.querySelector(".mobile-overlay")

  if (mobileBtn) {
    mobileBtn.addEventListener("click", toggleMobileMenu)
  }

  if (overlay) {
    overlay.addEventListener("click", closeMobileMenu)
  }

  // Cerrar menú al hacer clic en enlaces del sidebar
  const sidebarLinks = document.querySelectorAll(".sidebar .nav-link")
  sidebarLinks.forEach((link) => {
    link.addEventListener("click", () => {
      if (window.innerWidth <= 768) {
        closeMobileMenu()
      }
    })
  })

  // Asegurar que el botón sea visible en mobile
  if (window.innerWidth <= 768) {
    mobileBtn.style.display = "block"
  }
}

function toggleMobileMenu() {
  const sidebar = document.querySelector(".sidebar")
  const overlay = document.querySelector(".mobile-overlay")
  const mobileBtn = document.querySelector(".mobile-menu-btn")

  if (sidebar && overlay && mobileBtn) {
    if (sidebar.classList.contains("show")) {
      closeMobileMenu()
    } else {
      openMobileMenu()
    }
  }
}

function openMobileMenu() {
  const sidebar = document.querySelector(".sidebar")
  const overlay = document.querySelector(".mobile-overlay")
  const mobileBtn = document.querySelector(".mobile-menu-btn")

  if (sidebar && overlay && mobileBtn) {
    sidebar.classList.add("show")
    overlay.style.display = "block"
    setTimeout(() => overlay.classList.add("show"), 10)
    document.body.style.overflow = "hidden"

    // Cambiar icono a X
    mobileBtn.innerHTML = '<i class="fas fa-times"></i>'
  }
}

function closeMobileMenu() {
  const sidebar = document.querySelector(".sidebar")
  const overlay = document.querySelector(".mobile-overlay")
  const mobileBtn = document.querySelector(".mobile-menu-btn")

  if (sidebar && overlay && mobileBtn) {
    sidebar.classList.remove("show")
    overlay.classList.remove("show")
    setTimeout(() => {
      overlay.style.display = "none"
    }, 300)
    document.body.style.overflow = ""

    // Cambiar icono a hamburguesa
    mobileBtn.innerHTML = '<i class="fas fa-bars"></i>'
  }
}

// Cerrar menú al cambiar tamaño de ventana
window.addEventListener("resize", () => {
  if (window.innerWidth > 768) {
    const sidebar = document.querySelector(".sidebar")
    const overlay = document.querySelector(".mobile-overlay")

    if (sidebar && overlay) {
      sidebar.classList.remove("show")
      overlay.classList.remove("show")
      overlay.style.display = "none"
      document.body.style.overflow = ""
    }
  }
})

// ================================
// FORM VALIDATION
// ================================
function initFormValidation() {
  // Confirmar eliminaciones
  const deleteButtons = document.querySelectorAll(".btn-delete, .btn-outline-danger")
  deleteButtons.forEach((button) => {
    button.addEventListener("click", (e) => {
      if (button.onclick || button.getAttribute("onclick")) {
        return // Si ya tiene onclick, no interferir
      }
      if (!confirm("¿Está seguro de que desea eliminar este elemento?")) {
        e.preventDefault()
      }
    })
  })

  // Validación de formularios en tiempo real
  const forms = document.querySelectorAll("form")
  forms.forEach((form) => {
    form.addEventListener("submit", function (e) {
      if (!validateForm(this)) {
        e.preventDefault()
      }
    })
  })
}

function validateForm(form) {
  let isValid = true
  const inputs = form.querySelectorAll("input[required], select[required]")

  inputs.forEach((input) => {
    if (!input.value.trim()) {
      showFieldError(input, "Este campo es requerido")
      isValid = false
    } else {
      clearFieldError(input)
    }

    // Validaciones específicas
    if (input.type === "email" && input.value && !isValidEmail(input.value)) {
      showFieldError(input, "Ingrese un email válido")
      isValid = false
    }

    if (input.type === "number" && input.value && Number.parseFloat(input.value) < 0) {
      showFieldError(input, "El valor debe ser mayor o igual a 0")
      isValid = false
    }
  })

  return isValid
}

function showFieldError(input, message) {
  clearFieldError(input)
  input.classList.add("is-invalid")

  const errorDiv = document.createElement("div")
  errorDiv.className = "invalid-feedback"
  errorDiv.textContent = message
  input.parentNode.appendChild(errorDiv)
}

function clearFieldError(input) {
  input.classList.remove("is-invalid")
  const errorDiv = input.parentNode.querySelector(".invalid-feedback")
  if (errorDiv) {
    errorDiv.remove()
  }
}

function isValidEmail(email) {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  return emailRegex.test(email)
}

// ================================
// ALERTS MANAGEMENT
// ================================
function initAlerts() {
  // Auto-hide alerts
  const alerts = document.querySelectorAll(".alert")
  alerts.forEach((alert) => {
    setTimeout(() => {
      if (alert && alert.parentNode) {
        alert.style.opacity = "0"
        alert.style.transform = "translateY(-20px)"
        setTimeout(() => {
          if (alert.parentNode) {
            alert.remove()
          }
        }, 300)
      }
    }, 5000)
  })
}

// ================================
// ANIMATIONS
// ================================
function initAnimations() {
  // Añadir clase fade-in a elementos
  const cards = document.querySelectorAll(".card")
  cards.forEach((card, index) => {
    setTimeout(() => {
      card.classList.add("fade-in")
    }, index * 100)
  })
}

// ================================
// UTILITY FUNCTIONS
// ================================

// Función para mostrar loading
function showLoading() {
  const loadingDiv = document.createElement("div")
  loadingDiv.id = "loading"
  loadingDiv.innerHTML = '<div class="spinner"></div>'
  loadingDiv.style.cssText = `
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(255,255,255,0.8);
      display: flex;
      justify-content: center;
      align-items: center;
      z-index: 9999;
  `
  document.body.appendChild(loadingDiv)
}

function hideLoading() {
  const loadingDiv = document.getElementById("loading")
  if (loadingDiv) {
    loadingDiv.remove()
  }
}

// Función para mostrar notificaciones toast
function showToast(message, type = "info") {
  const toast = document.createElement("div")
  toast.className = `alert alert-${type} position-fixed`
  toast.style.cssText = `
    top: 80px;
    right: 20px;
    z-index: 1060;
    min-width: 300px;
    animation: slideInRight 0.3s ease;
  `
  toast.innerHTML = `
    <i class="fas fa-${type === "success" ? "check-circle" : type === "danger" ? "exclamation-triangle" : "info-circle"} me-2"></i>
    ${message}
    <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
  `

  document.body.appendChild(toast)

  setTimeout(() => {
    if (toast.parentNode) {
      toast.style.animation = "slideOutRight 0.3s ease"
      setTimeout(() => toast.remove(), 300)
    }
  }, 4000)
}

// Función para confirmar acciones
function confirmAction(message, callback) {
  if (confirm(message)) {
    callback()
  }
}

// Función para formatear números
function formatNumber(number, decimals = 2) {
  return new Intl.NumberFormat("es-ES", {
    minimumFractionDigits: decimals,
    maximumFractionDigits: decimals,
  }).format(number)
}

// Función para formatear moneda
function formatCurrency(amount) {
  return new Intl.NumberFormat("es-ES", {
    style: "currency",
    currency: "USD",
  }).format(amount)
}

// ================================
// KEYBOARD SHORTCUTS
// ================================
document.addEventListener("keydown", (e) => {
  // Ctrl/Cmd + M para toggle sidebar en mobile
  if ((e.ctrlKey || e.metaKey) && e.key === "m" && window.innerWidth <= 768) {
    e.preventDefault()
    const toggleBtn = document.querySelector(".mobile-menu-btn")
    if (toggleBtn) {
      toggleBtn.click()
    }
  }

  // Escape para cerrar sidebar
  if (e.key === "Escape") {
    closeMobileMenu()
  }
})

// ================================
// CSS ANIMATIONS KEYFRAMES (via JS)
// ================================
const style = document.createElement("style")
style.textContent = `
  @keyframes slideInRight {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
  }
  
  @keyframes slideOutRight {
    from { transform: translateX(0); opacity: 1; }
    to { transform: translateX(100%); opacity: 0; }
  }
`
document.head.appendChild(style)
