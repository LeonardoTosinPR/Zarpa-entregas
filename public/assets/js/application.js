document.addEventListener("DOMContentLoaded", function () {
  setupImagePreview();
  setupFormValidation();
  setupOrderPricing();
});

function setupImagePreview() {
  const imagePreviewInput = document.getElementById("image_preview_input");
  const preview = document.getElementById("image_preview");
  const imagePreviewSubmit = document.getElementById("image_preview_submit");

  if (!(imagePreviewInput && preview)) return;

  imagePreviewInput.style.display = "none";
  imagePreviewSubmit.style.display = "none";

  preview.addEventListener("click", function () {
    imagePreviewInput.click();
  });

  imagePreviewInput.addEventListener("change", function (event) {
    const file = event.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function (e) {
      preview.src = e.target.result;
      imagePreviewSubmit.style.display = "block";
    };
    reader.readAsDataURL(file);
  });
}

function setupFormValidation() {
  document.querySelectorAll("[data-validate-form]").forEach(function (form) {
    const password = form.querySelector("[data-password]");
    const confirmation = form.querySelector("[data-password-confirmation]");
    const meter = form.querySelector("[data-password-meter]");

    form
      .querySelectorAll(
        "input[required], textarea[required], select[required], input[type='email'], input[minlength]",
      )
      .forEach(function (input) {
        input.addEventListener("input", function () {
          validateInput(input);
          if (password && confirmation)
            validatePasswordMatch(password, confirmation);
          if (password && meter) updatePasswordMeter(password, meter);
        });

        input.addEventListener("blur", function () {
          validateInput(input);
        });
      });

    form.addEventListener("submit", function (event) {
      let valid = true;

      form
        .querySelectorAll(
          "input[required], textarea[required], select[required], input[type='email'], input[minlength]",
        )
        .forEach(function (input) {
          if (!validateInput(input)) valid = false;
        });

      if (
        password &&
        confirmation &&
        !validatePasswordMatch(password, confirmation)
      ) {
        valid = false;
      }

      if (!valid) {
        event.preventDefault();
        event.stopPropagation();
        const firstInvalid = form.querySelector(".is-invalid");
        if (firstInvalid) firstInvalid.focus();
      }
    });
  });
}

function validateInput(input) {
  const value = input.value.trim();
  let message = "";

  if (input.required && value === "") {
    message = "Preencha este campo.";
  } else if (
    input.type === "email" &&
    value !== "" &&
    !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)
  ) {
    message = "Informe um e-mail valido.";
  } else if (
    input.minLength > 0 &&
    value.length > 0 &&
    value.length < input.minLength
  ) {
    message = "Use pelo menos " + input.minLength + " caracteres.";
  } else if (
    input.type === "date" &&
    value !== "" &&
    new Date(value) > new Date()
  ) {
    message = "A data nao pode ser futura.";
  }

  setInputState(input, message);
  return message === "";
}

function validatePasswordMatch(password, confirmation) {
  const mismatch =
    confirmation.value !== "" && password.value !== confirmation.value;
  setInputState(confirmation, mismatch ? "As senhas precisam ser iguais." : "");
  return !mismatch;
}

function setInputState(input, message) {
  const feedback = input
    .closest(".mb-3, .mb-4, .col-12, .form-section")
    ?.querySelector(".invalid-feedback");

  input.classList.toggle("is-invalid", message !== "");
  input.classList.toggle(
    "is-valid",
    message === "" && input.value.trim() !== "",
  );

  if (feedback && message !== "") {
    feedback.textContent = message;
  }
}

function updatePasswordMeter(password, meter) {
  const value = password.value;
  let level = 0;

  if (value.length >= 6) level++;
  if (/[A-Z]/.test(value) || /\d/.test(value)) level++;
  if (/[^A-Za-z0-9]/.test(value) || value.length >= 10) level++;

  meter.dataset.level = String(level);
}

function setupOrderPricing() {
  document.querySelectorAll("[data-order-pricing]").forEach(function (form) {
    const packageSize = form.querySelector("[data-package-size]");
    const isFragile = form.querySelector("[data-is-fragile]");
    const distanceKm = form.querySelector("[data-distance-km]");
    const total = form.querySelector("[data-order-total]");
    const pickupAddress = form.querySelector("[data-pickup-address]");
    const deliveryAddress = form.querySelector("[data-delivery-address]");
    const mapsLink = form.querySelector("[data-order-maps-link]");

    const update = function () {
      updateOrderTotal(packageSize, isFragile, distanceKm, total);
      updateMapsLink(pickupAddress, deliveryAddress, mapsLink);
    };

    [
      packageSize,
      isFragile,
      distanceKm,
      pickupAddress,
      deliveryAddress,
    ].forEach(function (field) {
      if (!field) return;
      field.addEventListener("input", update);
      field.addEventListener("change", update);
    });

    if (mapsLink) {
      mapsLink.addEventListener("click", function (event) {
        if (mapsLink.classList.contains("disabled")) {
          event.preventDefault();
        }
      });
    }

    update();
  });
}

function updateOrderTotal(packageSize, isFragile, distanceKm, total) {
  if (!(packageSize && isFragile && distanceKm && total)) return;

  const basePrices = {
    pequeno: 10,
    medio: 15,
    grande: 20,
  };

  const base = basePrices[packageSize.value] || basePrices.pequeno;
  const fragileFee = isFragile.checked ? 5 : 0;
  const distance = Math.max(
    0,
    parseFloat(String(distanceKm.value).replace(",", ".")) || 0,
  );
  const finalPrice = base + fragileFee + distance * 0.1;

  total.value = finalPrice.toLocaleString("pt-BR", {
    style: "currency",
    currency: "BRL",
  });
}

function updateMapsLink(pickupAddress, deliveryAddress, mapsLink) {
  if (!(pickupAddress && deliveryAddress && mapsLink)) return;

  const origin = pickupAddress.value.trim();
  const destination = deliveryAddress.value.trim();
  const disabled = origin === "" || destination === "";

  mapsLink.classList.toggle("disabled", disabled);
  mapsLink.setAttribute("aria-disabled", disabled ? "true" : "false");

  if (disabled) {
    mapsLink.href = "#";
    return;
  }

  mapsLink.href =
    "https://www.google.com/maps/dir/?api=1&origin=" +
    encodeURIComponent(origin) +
    "&destination=" +
    encodeURIComponent(destination) +
    "&travelmode=driving";
}

/**
 * Funções Ajax para gerenciar tags
 */

/**
 * Faz uma requisição Ajax para listar todas as tags
 * @param {Function} onSuccess - Callback executado com sucesso (recebe data como parâmetro)
 * @param {Function} onError - Callback executado em caso de erro (opcional)
 */
function listTagsAjax(onSuccess, onError) {
  fetch("/api/tags", {
    method: "GET",
    headers: {
      Accept: "application/json",
    },
  })
    .then((response) => {
      if (!response.ok) {
        throw new Error("Erro na requisição: " + response.status);
      }
      return response.json();
    })
    .then((data) => {
      if (data.success) {
        onSuccess(data.data);
      } else {
        throw new Error(data.message || "Erro ao carregar tags");
      }
    })
    .catch((error) => {
      console.error("Erro ao listar tags:", error);
      if (onError) {
        onError(error.message);
      }
    });
}

/**
 * Faz uma requisição Ajax para criar uma nova tag
 * @param {Object} tagData - Objeto contendo {name: string, color: string}
 * @param {Function} onSuccess - Callback executado com sucesso (recebe data como parâmetro)
 * @param {Function} onError - Callback executado em caso de erro (opcional)
 */
function createTagAjax(tagData, onSuccess, onError) {
  fetch("/api/tags", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
      Accept: "application/json",
    },
    body: new URLSearchParams({
      "tag[name]": tagData.name,
      "tag[color]": tagData.color,
    }),
  })
    .then((response) => {
      if (!response.ok && response.status !== 400 && response.status !== 403) {
        throw new Error("Erro na requisição: " + response.status);
      }
      return response.json();
    })
    .then((data) => {
      if (data.success) {
        onSuccess(data.data);
      } else {
        throw new Error(data.message || "Erro ao criar tag");
      }
    })
    .catch((error) => {
      console.error("Erro ao criar tag:", error);
      if (onError) {
        onError(error.message);
      }
    });
}

/**
 * Faz uma requisição Ajax para deletar uma tag
 * @param {number} tagId - ID da tag a deletar
 * @param {Function} onSuccess - Callback executado com sucesso
 * @param {Function} onError - Callback executado em caso de erro (opcional)
 */
function deleteTagAjax(tagId, onSuccess, onError) {
  fetch("/api/tags/" + tagId, {
    method: "DELETE",
    headers: {
      Accept: "application/json",
    },
  })
    .then((response) => {
      if (!response.ok && response.status !== 403 && response.status !== 404) {
        throw new Error("Erro na requisição: " + response.status);
      }
      return response.json();
    })
    .then((data) => {
      if (data.success) {
        onSuccess();
      } else {
        throw new Error(data.message || "Erro ao deletar tag");
      }
    })
    .catch((error) => {
      console.error("Erro ao deletar tag:", error);
      if (onError) {
        onError(error.message);
      }
    });
}

/**
 * Renderiza uma tag no HTML
 * @param {Object} tag - Objeto contendo {id, name, color, badgeClass}
 * @returns {HTMLElement} Elemento da tag
 */
function createTagElement(tag) {
  const row = document.createElement("tr");
  row.id = "tag-" + tag.id;

  row.innerHTML = `
    <td>
      <span class="badge ${tag.badgeClass}">
        <i class="bi bi-tag"></i> ${tag.name}
      </span>
    </td>
    <td>0</td>
    <td class="text-end">
      <button class="btn btn-sm btn-outline-danger delete-tag-btn" data-tag-id="${tag.id}" title="Remover etiqueta" aria-label="Remover etiqueta">
        <i class="bi bi-trash"></i>
      </button>
    </td>
  `;

  const deleteBtn = row.querySelector(".delete-tag-btn");
  deleteBtn.addEventListener("click", function () {
    if (confirm("Remover esta etiqueta e todos os seus vinculos?")) {
      deleteTagAjax(
        tag.id,
        function () {
          row.remove();
          alert("Etiqueta removida com sucesso!");
        },
        function (error) {
          alert("Erro ao remover: " + error);
        },
      );
    }
  });

  return row;
}

/**
 * Inicializa o gerenciador de tags com Ajax
 */
function setupAjaxTags() {
  const createForm = document.querySelector('form[action*="tags"]');
  const tagsTableBody = document.querySelector("table tbody");

  if (!createForm || !tagsTableBody) return;

  // Substituir envio do formulário por Ajax
  createForm.addEventListener("submit", function (event) {
    event.preventDefault();

    const nameInput = createForm.querySelector('[name="tag[name]"]');
    const colorInput = createForm.querySelector('[name="tag[color]"]');

    if (!nameInput || !colorInput) return;

    const tagData = {
      name: nameInput.value.trim(),
      color: colorInput.value,
    };

    // Validação básica
    if (!tagData.name) {
      alert("Informe o nome da etiqueta");
      nameInput.focus();
      return;
    }

    createTagAjax(
      tagData,
      function (newTag) {
        // Limpar formulário
        nameInput.value = "";
        colorInput.value = "secondary";

        // Adicionar nova tag à tabela
        const newRow = createTagElement(newTag);
        tagsTableBody.appendChild(newRow);

        // Atualizar contador de tags
        const badgeCount = document.querySelector(".badge.text-bg-light");
        if (badgeCount) {
          const count = parseInt(badgeCount.textContent) || 0;
          badgeCount.textContent =
            count + 1 + " etiqueta" + (count + 1 === 1 ? "" : "s");
        }

        alert("Etiqueta criada com sucesso!");
      },
      function (error) {
        alert("Erro: " + error);
      },
    );
  });

  // Adicionar listeners aos botões de delete existentes
  document.querySelectorAll(".delete-tag-btn").forEach(function (btn) {
    btn.addEventListener("click", function (event) {
      event.preventDefault();
      const tagId = btn.getAttribute("data-tag-id");

      if (confirm("Remover esta etiqueta e todos os seus vinculos?")) {
        deleteTagAjax(
          tagId,
          function () {
            const tagRow = document.getElementById("tag-" + tagId);
            if (tagRow) {
              tagRow.remove();
            }

            // Atualizar contador
            const badgeCount = document.querySelector(".badge.text-bg-light");
            if (badgeCount) {
              const count = parseInt(badgeCount.textContent) || 0;
              if (count > 1) {
                badgeCount.textContent = count - 1 + " etiquetas";
              }
            }

            alert("Etiqueta removida com sucesso!");
          },
          function (error) {
            alert("Erro: " + error);
          },
        );
      }
    });
  });
}

// Inicializar Ajax tags quando a página carregar
document.addEventListener("DOMContentLoaded", function () {
  setupImagePreview();
  setupFormValidation();
  setupOrderPricing();
  setupAjaxTags();
});
