document.addEventListener("DOMContentLoaded", function () {
  setupImagePreview();
  setupFormValidation();
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

    form.querySelectorAll("input[required], input[type='email'], input[minlength]").forEach(function (input) {
      input.addEventListener("input", function () {
        validateInput(input);
        if (password && confirmation) validatePasswordMatch(password, confirmation);
        if (password && meter) updatePasswordMeter(password, meter);
      });

      input.addEventListener("blur", function () {
        validateInput(input);
      });
    });

    form.addEventListener("submit", function (event) {
      let valid = true;

      form.querySelectorAll("input[required], input[type='email'], input[minlength]").forEach(function (input) {
        if (!validateInput(input)) valid = false;
      });

      if (password && confirmation && !validatePasswordMatch(password, confirmation)) {
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
  } else if (input.type === "email" && value !== "" && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
    message = "Informe um e-mail valido.";
  } else if (input.minLength > 0 && value.length > 0 && value.length < input.minLength) {
    message = "Use pelo menos " + input.minLength + " caracteres.";
  } else if (input.type === "date" && value !== "" && new Date(value) > new Date()) {
    message = "A data nao pode ser futura.";
  }

  setInputState(input, message);
  return message === "";
}

function validatePasswordMatch(password, confirmation) {
  const mismatch = confirmation.value !== "" && password.value !== confirmation.value;
  setInputState(confirmation, mismatch ? "As senhas precisam ser iguais." : "");
  return !mismatch;
}

function setInputState(input, message) {
  const feedback = input.closest(".mb-3, .mb-4")?.querySelector(".invalid-feedback");

  input.classList.toggle("is-invalid", message !== "");
  input.classList.toggle("is-valid", message === "" && input.value.trim() !== "");

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
