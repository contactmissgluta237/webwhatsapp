window.AddressModalHandler = (function() {
    function init(customerHandler) {
        // Before showing the modal, check if a customer is selected
        $('#addAddressModal').on('show.bs.modal', function (event) {
            const selectedCustomerData = customerHandler.getSelectedCustomerData();
            if (!selectedCustomerData || !selectedCustomerData.id) {
                alert("Veuillez d'abord sélectionner un client.");
                event.preventDefault(); // Prevent modal from opening
            }
        });

        // Handle new address form submission
        $("#add-address-form").on("submit", function(e) {
            e.preventDefault();

            const selectedCustomerData = customerHandler.getSelectedCustomerData();
            if (!selectedCustomerData || !selectedCustomerData.id) {
                alert("Veuillez sélectionner un client avant d'ajouter une adresse.");
                return;
            }

            const customerIdForAddress = selectedCustomerData.id; // Use the correct customer_id

            const formData = {};
            $(this).find("input, select, textarea").each(function() {
                const input = $(this);
                if (input.attr("name")) {
                    if (input.attr("type") === "checkbox") {
                        formData[input.attr("name")] = input.is(':checked');
                    } else {
                        formData[input.attr("name")] = input.val();
                    }
                }
            });

            const saveAddressBtn = $("#saveAddressBtn");
            const addressLoader = $("#addressLoader");

            saveAddressBtn.prop("disabled", true);
            addressLoader.removeClass("d-none");

            console.log("Sending address data:", formData);
            ApiService.createCustomerDeliveryAddress(customerIdForAddress, formData)
                .done(function(response) {
                    console.log("Add Address Success Response:", response);
                    alert("Adresse ajoutée avec succès!");
                    $('#addAddressModal').modal('hide'); // Close the modal
                    $("#add-address-form")[0].reset(); // Clear the form
                    customerHandler.refreshCustomersAndAddresses(selectedCustomerData.id); // Re-fetch *only* the selected customer and refresh addresses
                })
                .fail(function(jqXHR) {
                    console.error("Add Address Error Response:", jqXHR);
                    let errorMsg = "Erreur lors de l'ajout de l'adresse.";
                    if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                        errorMsg += ` ${jqXHR.responseJSON.message}`;
                    }
                    alert(errorMsg);
                })
                .always(function() {
                    saveAddressBtn.prop("disabled", false);
                    addressLoader.addClass("d-none");
                });
        });
    }

    return {
        init: init
    };
})();
