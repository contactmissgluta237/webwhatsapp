function confirmAction(params) {
    const methodName = params.method || "";
    const methodParams = params.parameters || [];
    const titleValue = params.title || "Confirmation";
    const textValue =
        params.text || "Voulez-vous vraiment effectuer cette action ?";
    const iconValue = params.icon || "question";
    const confirmTextValue = params.confirmText || "Oui, confirmer";
    const cancelTextValue = params.cancelText || "Annuler";

    Swal.fire({
        title: titleValue,
        text: textValue,
        icon: iconValue,
        showCancelButton: true,
        confirmButtonColor: "#198754",
        cancelButtonColor: "#6c757d",
        confirmButtonText:
            '<i class="ti ti-check me-1"></i>' + confirmTextValue,
        cancelButtonText: '<i class="ti ti-x me-1"></i>' + cancelTextValue,
        reverseButtons: true,
        focusCancel: true,
        customClass: {
            confirmButton: "btn btn-success me-2",
            cancelButton: "btn btn-secondary ms-2",
            actions: "gap-2",
        },
        buttonsStyling: false,
    }).then((result) => {
        if (result.isConfirmed) {
            if (typeof Livewire !== "undefined") {
                const componentId = document
                    .querySelector("[wire\\:id]")
                    .getAttribute("wire:id");
                Livewire.find(componentId).call(methodName, ...methodParams);
            }
        }
    });
}

function confirmActionWithInput(params) {
    const actionId = params.id || "";
    const methodName = params.method || "";
    const methodParams = params.parameters || [];
    const confirmWordValue = params.confirmWord || "confirmer";
    const titleValue = params.title || "Confirmation avec saisie";
    const textValue =
        params.text || "Vous êtes sur le point d'effectuer une action importante.";
    const entityNameValue = params.entityName || "";
    const confirmTextValue = params.confirmText || "Confirmer l'action";
    const cancelTextValue = params.cancelText || "Annuler";
    const actionInProgressText = params.actionInProgressText || "Action en cours...";
    const iconValue = params.icon || "warning";
    const confirmButtonIcon = params.confirmButtonIcon || "ti ti-check";

    const uniqueInputId = "confirmActionInput_" + actionId;

    Swal.fire({
        title: titleValue,
        html: `
            <p class="mb-3">${textValue}</p>
            ${entityNameValue ? `<p class="fw-bold ${iconValue === "warning" ? "text-warning" : "text-info"} mb-3">${entityNameValue}</p>` : ""}
            <p class="mb-3">Tapez <strong>"${confirmWordValue}"</strong> pour confirmer :</p>
            <input type="text" id="${uniqueInputId}" class="form-control" placeholder="Tapez "${confirmWordValue}" pour confirmer">
        `,
        icon: iconValue,
        showCancelButton: true,
        confirmButtonColor: "#198754",
        cancelButtonColor: "#6c757d",
        confirmButtonText: `<i class="${confirmButtonIcon} me-1"></i>${confirmTextValue}`,
        cancelButtonText: '<i class="ti ti-x me-1"></i>' + cancelTextValue,
        reverseButtons: true,
        focusCancel: true,
        customClass: {
            confirmButton: "btn btn-success me-2",
            cancelButton: "btn btn-secondary ms-2",
            actions: "gap-2",
        },
        buttonsStyling: false,
        preConfirm: () => {
            const input = document.getElementById(uniqueInputId);
            if (
                input.value.toLowerCase().trim() !==
                confirmWordValue.toLowerCase()
            ) {
                Swal.showValidationMessage(
                    `Vous devez taper "${confirmWordValue}" pour confirmer l'action`,
                );
                return false;
            }
            return true;
        },
        didOpen: () => {
            const input = document.getElementById(uniqueInputId);
            const confirmButton = Swal.getConfirmButton();

            confirmButton.disabled = true;
            confirmButton.style.opacity = "0.5";

            input.addEventListener("input", function () {
                const isValid =
                    this.value.toLowerCase().trim() ===
                    confirmWordValue.toLowerCase();
                confirmButton.disabled = !isValid;
                confirmButton.style.opacity = isValid ? "1" : "0.5";
            });

            input.focus();
        },
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: actionInProgressText,
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                },
            });

            if (typeof Livewire !== "undefined") {
                const componentId = document
                    .querySelector("[wire\\:id]")
                    .getAttribute("wire:id");
                Livewire.find(componentId).call(methodName, ...methodParams);
            }
        }
    });
}
