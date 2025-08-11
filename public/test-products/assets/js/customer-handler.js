window.CustomerHandler = (function() {
    let allCustomers = []; // To store all customers with their addresses
    let selectedCustomerData = null; // To store the currently selected customer's full data

    // Function to populate customer addresses dropdown
    function populateCustomerAddresses(customerData) {
        const customerAddressSelect = $("#customer_address");
        customerAddressSelect.empty().append('<option value="">Sélectionnez une adresse</option>');
        console.log("populateCustomerAddresses called with:", customerData);

        if (customerData && customerData.deliveryAddresses) {
            console.log("Found delivery addresses:", customerData.deliveryAddresses);
            customerData.deliveryAddresses.forEach(function(address) {
                customerAddressSelect.append(`<option value="${address.id}">${address.address}</option>`);
            });
            console.log("Number of addresses appended:", customerData.deliveryAddresses.length);
        } else {
            console.log("No delivery addresses found for this customer.");
        }
    }

    // Function to re-fetch customers and update UI
    function refreshCustomersAndAddresses(customerIdToRefresh = null) {
        console.log("refreshCustomersAndAddresses called with customerIdToRefresh:", customerIdToRefresh);
        if (customerIdToRefresh) {
            // Fetch only the specific customer to update their addresses
            ApiService.fetchCustomer(customerIdToRefresh)
                .done(function(response) {
                    console.log("Response from fetchCustomer:", response);
                    if (response && response.data) {
                        const updatedCustomer = response.data; // Now expects a single object
                        // Find and replace the updated customer in allCustomers array
                        const index = allCustomers.findIndex(cust => cust.id == updatedCustomer.id);
                        if (index !== -1) {
                            allCustomers[index] = updatedCustomer;
                            selectedCustomerData = updatedCustomer; // Update selectedCustomerData if it's the one being refreshed
                            console.log("allCustomers updated. selectedCustomerData:", selectedCustomerData);
                        } else {
                            console.warn("Updated customer not found in allCustomers array. This might indicate a data mismatch.");
                        }
                        populateCustomerAddresses(updatedCustomer);
                    } else {
                        console.warn("fetchCustomer response.data is empty or null.");
                    }
                })
                .fail(function(jqXHR) {
                    console.error("Error fetching single customer:", jqXHR.responseText);
                });
        } else {
            // Initial fetch of all customers for the dropdown
            ApiService.fetchCustomers()
                .done(function(response) {
                    console.log("Response from fetchCustomers (initial load):", response);
                    if (response && response.data) {
                        allCustomers = response.data; // Store all customers
                        const customerSelect = $("#customer");
                        customerSelect.empty().append('<option value="">Sélectionnez un client</option>');
                        response.data.forEach(function(customer) {
                            customerSelect.append(`<option value="${customer.id}">${customer.full_name}</option>`);
                        });
                        console.log("Initial customer dropdown populated.");
                    } else {
                        console.warn("fetchCustomers response.data is empty or null.");
                    }
                })
                .fail(function(jqXHR) {
                    console.error("Error fetching customers (initial load):", jqXHR.responseText);
                });
        }
    }

    // Event listener for Customer selection change
    $("#customer").on("change", function() {
        const selectedId = $(this).val();
        console.log("Customer selected:", selectedId);
        if (selectedId) {
            ApiService.fetchCustomer(selectedId)
                .done(function(response) {
                    if (response && response.data) {
                        selectedCustomerData = response.data; // Update selectedCustomerData with fresh data
                        populateCustomerAddresses(selectedCustomerData);
                        console.log("Customer data and addresses refreshed for ID:", selectedId);
                    }
                })
                .fail(function(jqXHR) {
                    console.error("Error fetching customer details on selection:", jqXHR.responseText);
                    selectedCustomerData = null; // Clear selected customer data on error
                    populateCustomerAddresses(null); // Clear addresses dropdown
                });
        } else {
            selectedCustomerData = null; // Clear selected customer data if no customer is selected
            populateCustomerAddresses(null); // Clear addresses dropdown
        }
    });

    return {
        init: function() {
            refreshCustomersAndAddresses();
        },
        getSelectedCustomerData: function() {
            return selectedCustomerData;
        },
        refreshCustomersAndAddresses: refreshCustomersAndAddresses // Expose for address-modal-handler
    };
})();
