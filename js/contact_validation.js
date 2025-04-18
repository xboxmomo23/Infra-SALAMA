document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("contactForm");

  if (form) {
    form.addEventListener("submit", function (event) {
      // Empêcher l'envoi du formulaire si la validation échoue
      if (!validateForm()) {
        event.preventDefault();
      }
    });
  }

  function validateForm() {
    let isValid = true;

    // Vérifier les champs requis
    const requiredFields = form.querySelectorAll("[required]");

    requiredFields.forEach((field) => {
      // Retirer les messages d'erreur précédents
      removeErrorMessage(field);

      if (!field.value.trim()) {
        displayErrorMessage(field, "Ce champ est obligatoire");
        isValid = false;
      }
    });

    // Validation de l'email
    const emailField = document.getElementById("email");
    if (emailField && emailField.value.trim()) {
      const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailPattern.test(emailField.value.trim())) {
        displayErrorMessage(
          emailField,
          "Veuillez entrer une adresse email valide"
        );
        isValid = false;
      }
    }

    // Validation du numéro de téléphone (si rempli)
    const phoneField = document.getElementById("phone");
    if (phoneField && phoneField.value.trim()) {
      const phonePattern =
        /^[+]?[(]?[0-9]{3}[)]?[-\s.]?[0-9]{3}[-\s.]?[0-9]{4,6}$/;
      if (!phonePattern.test(phoneField.value.trim())) {
        displayErrorMessage(
          phoneField,
          "Veuillez entrer un numéro de téléphone valide"
        );
        isValid = false;
      }
    }

    return isValid;
  }

  function displayErrorMessage(field, message) {
    const parent = field.parentElement;
    const errorDiv = document.createElement("div");
    errorDiv.className = "invalid-feedback d-block";
    errorDiv.textContent = message;

    // Ajouter une classe pour montrer l'erreur
    field.classList.add("is-invalid");

    // Ajouter le message d'erreur
    parent.appendChild(errorDiv);
  }

  function removeErrorMessage(field) {
    const parent = field.parentElement;
    const errorDiv = parent.querySelector(".invalid-feedback");

    // Supprimer la classe d'erreur
    field.classList.remove("is-invalid");

    // Supprimer le message d'erreur s'il existe
    if (errorDiv) {
      parent.removeChild(errorDiv);
    }
  }
});
