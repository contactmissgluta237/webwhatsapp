$(document).ready(function() {
    // Initialize core services
    CartService.init();
    CustomerHandler.init();
    ProductHandler.init();
    AddressModalHandler.init(CustomerHandler); // Pass CustomerHandler to access selected customer data and refresh function

    // Fetch initial data for dropdowns
    ApiService.fetchDistributionCenters()
        .done(function(response) {
            if (response && response.data) {
                const dcSelect = $("#distribution_center");
                response.data.forEach(function(dc) {
                    dcSelect.append(`<option value="${dc.id}">${dc.name}</option>`);
                });
            }
        })
        .fail(function(jqXHR) {
            console.error("Error fetching distribution centers:", jqXHR.responseText);
        });

    ApiService.fetchPaymentMethods()
        .done(function(response) {
            if (response && response.data) {
                const paymentMethodSelect = $("#payment_method");
                response.data.forEach(function(method) {
                    paymentMethodSelect.append(`<option value="${method.value}">${method.label}</option>`);
                });
            }
        })
        .fail(function(jqXHR) {
            console.error("Error fetching payment methods:", jqXHR.responseText);
        });

    ApiService.fetchDeliveryTypes()
        .done(function(response) {
            if (response && response.data) {
                const deliveryTypeSelect = $("#delivery_type");
                response.data.forEach(function(type) {
                    deliveryTypeSelect.append(`<option value="${type.value}">${type.label}</option>`);
                });
            }
        })
        .fail(function(jqXHR) {
            console.error("Error fetching delivery types:", jqXHR.responseText);
        });

    // Simulate selection of Payment Method and Delivery Type
    // This is a placeholder and would typically be driven by user interaction
    // or pre-selected values.
    $("#payment_method").val("cash"); // Example: set Cash as default
    $("#delivery_type").val("home_delivery"); // Example: set Home Delivery as default
});