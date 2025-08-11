/**
 * Sweet Alert Confirm Actions
 * 
 * Ce fichier contient des fonctions qui permettent d'afficher des boîtes de dialogue de confirmation
 * avant d'exécuter des actions spécifiques dans les composants Livewire.
 * 
 * IMPORTANT: Ces fonctions sont conçues pour être utilisées UNIQUEMENT avec des composants Livewire.
 * Le paramètre componentId doit toujours être fourni via $this->getId() depuis un composant Livewire.
 */

/**
 * Affiche une boîte de dialogue de confirmation simple avant d'exécuter une méthode sur un composant Livewire.
 * 
 * @param {Object} params - Les paramètres de configuration
 * @param {string} params.method - Nom de la méthode Livewire à appeler
 * @param {Array} params.parameters - Paramètres à passer à la méthode Livewire
 * @param {string} params.componentId - ID du composant Livewire (OBLIGATOIRE, obtenu via $this->getId())
 * @param {string} params.title - Titre de la boîte de dialogue
 * @param {string} params.text - Texte de la boîte de dialogue
 * @param {string} params.icon - Icône à afficher (success, error, warning, info, question)
 * @param {string} params.confirmText - Texte du bouton de confirmation
 * @param {string} params.cancelText - Texte du bouton d'annulation
 * 
 * Exemple d'utilisation dans un composant Livewire:
 * <button onclick="confirmAction({
 *    method: 'toggleStatus',
 *    parameters: [1],
 *    componentId: '{{ $this->getId() }}',
 *    title: 'Confirmation',
 *    text: 'Voulez-vous changer le statut?'
 * })">Changer statut</button>
 */
function confirmAction(params) {
    const methodName = params.method || "";
    const methodParams = params.parameters || [];
    const titleValue = params.title || "Confirmation";
    const textValue =
        params.text || "Voulez-vous vraiment effectuer cette action ?";
    const iconValue = params.icon || "question";
    const confirmTextValue = params.confirmText || "Oui, confirmer";
    const cancelTextValue = params.cancelText || "Annuler";
    const componentId = params.componentId || null;

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
                let targetComponent = null;
                
                // Si un ID de composant spécifique est fourni, l'utiliser
                if (componentId) {
                    try {
                        targetComponent = Livewire.find(componentId);
                    } catch (e) {
                        console.error("Erreur lors de la recherche du composant par ID");
                    }
                }
                
                // Si l'ID n'est pas fourni ou invalide, chercher l'élément parent avec wire:id
                if (!targetComponent) {
                    const triggeredFrom = document.activeElement;
                    const closestWireElement = triggeredFrom.closest('[wire\\:id]');
                    
                    if (closestWireElement) {
                        const wireId = closestWireElement.getAttribute('wire:id');
                        try {
                            targetComponent = Livewire.find(wireId);
                        } catch (e) {}
                    }
                }
                
                // Exécuter seulement si on a trouvé un composant
                if (targetComponent) {
                    try {
                        targetComponent.call(methodName, ...methodParams);
                    } catch (e) {
                        console.error(`Erreur lors de l'appel de ${methodName}:`, e);
                        Swal.fire({
                            title: "Erreur",
                            text: `La méthode ${methodName} n'a pas pu être appelée.`,
                            icon: "error"
                        });
                    }
                } else {
                    Swal.fire({
                        title: "Erreur",
                        text: "Aucun composant Livewire trouvé. Spécifiez un ID de composant valide.",
                        icon: "error"
                    });
                }
            }
        }
    });
}

/**
 * Affiche une boîte de dialogue de confirmation avec saisie avant d'exécuter une méthode sur un composant Livewire.
 * L'utilisateur doit taper un mot spécifique pour confirmer l'action.
 * 
 * @param {Object} params - Les paramètres de configuration
 * @param {string} params.method - Nom de la méthode Livewire à appeler
 * @param {Array} params.parameters - Paramètres à passer à la méthode Livewire
 * @param {string} params.componentId - ID du composant Livewire (OBLIGATOIRE, obtenu via $this->getId())
 * @param {string} params.confirmWord - Mot à taper pour confirmer l'action
 * @param {string} params.title - Titre de la boîte de dialogue
 * @param {string} params.text - Texte de la boîte de dialogue
 * @param {string} params.entityName - Nom de l'entité concernée (affiché en gras)
 * @param {string} params.icon - Icône à afficher (success, error, warning, info, question)
 * @param {string} params.confirmText - Texte du bouton de confirmation
 * @param {string} params.cancelText - Texte du bouton d'annulation
 * @param {string} params.actionInProgressText - Texte affiché pendant le traitement
 * @param {string} params.confirmButtonIcon - Classe de l'icône du bouton de confirmation
 * @param {string} params.id - Identifiant unique pour l'input de confirmation
 * 
 * Exemple d'utilisation dans un composant Livewire:
 * <button onclick="confirmActionWithInput({
 *    method: 'deleteUser',
 *    parameters: [1],
 *    componentId: '{{ $this->getId() }}',
 *    confirmWord: 'supprimer',
 *    title: 'Supprimer l\'utilisateur',
 *    text: 'Cette action est irréversible.',
 *    entityName: 'Utilisateur: John Doe',
 *    icon: 'warning'
 * })">Supprimer</button>
 */
function confirmActionWithInput(params) {
    const actionId = params.id || "";
    const methodName = params.method || "";
    const methodParams = params.parameters || [];
    const confirmWordValue = params.confirmWord || "confirmer";
    const titleValue = params.title || "Confirmation avec saisie";
    const textValue =
        params.text ||
        "Vous êtes sur le point d'effectuer une action importante.";
    const entityNameValue = params.entityName || "";
    const confirmTextValue = params.confirmText || "Confirmer l'action";
    const cancelTextValue = params.cancelText || "Annuler";
    const actionInProgressText =
        params.actionInProgressText || "Action en cours...";
    const iconValue = params.icon || "warning";
    const confirmButtonIcon = params.confirmButtonIcon || "ti ti-check";
    const componentId = params.componentId || null;

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
                let targetComponent = null;
                
                // Si un ID de composant spécifique est fourni, l'utiliser
                if (componentId) {
                    try {
                        targetComponent = Livewire.find(componentId);
                    } catch (e) {}
                }
                
                // Si l'ID n'est pas fourni ou invalide, chercher l'élément parent avec wire:id
                if (!targetComponent) {
                    const triggeredFrom = document.activeElement;
                    const closestWireElement = triggeredFrom.closest('[wire\\:id]');
                    
                    if (closestWireElement) {
                        const wireId = closestWireElement.getAttribute('wire:id');
                        try {
                            targetComponent = Livewire.find(wireId);
                        } catch (e) {}
                    }
                }
                
                // Exécuter seulement si on a trouvé un composant
                if (targetComponent) {
                    try {
                        targetComponent.call(methodName, ...methodParams);
                    } catch (e) {
                        Swal.fire({
                            title: "Erreur",
                            text: `La méthode ${methodName} n'a pas pu être appelée.`,
                            icon: "error"
                        });
                    }
                } else {
                    Swal.fire({
                        title: "Erreur",
                        text: "Aucun composant Livewire trouvé. Spécifiez un ID de composant valide.",
                        icon: "error"
                    });
                }
            }
        }
    });
}