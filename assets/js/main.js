// Funciones JavaScript para mejorar la UX
document.addEventListener("DOMContentLoaded", () => {
  // Confirmar eliminaciones
  const deleteButtons = document.querySelectorAll(".btn-delete")
  deleteButtons.forEach((button) => {
    button.addEventListener("click", (e) => {
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

  // Auto-hide alerts
  const alerts = document.querySelectorAll(".alert")
  alerts.forEach((alert) => {
    setTimeout(() => {
      alert.style.opacity = "0"
      setTimeout(() => alert.remove(), 300)
    }, 5000)
  })

  // Añadir clase fade-in a elementos
  const cards = document.querySelectorAll(".card")
  cards.forEach((card, index) => {
    setTimeout(() => {
      card.classList.add("fade-in")
    }, index * 100)
  })
})

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
