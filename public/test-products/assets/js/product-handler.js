window.ProductHandler = (function() {
    let allProducts = []; // To store all products for the selected distribution center

    function init() {
        // Event listener for Distribution Center selection change
        $("#distribution_center").on("change", function() {
            const selectedDcId = $(this).val();
            const productSelect = $("#selectedProduct");
            productSelect.empty().append('<option value="">Sélectionner un produit</option>');
            productSelect.prop("disabled", true); // Disable until products are loaded
            allProducts = []; // Clear previous products
            $("#product-option-container").hide(); // Hide options when DC changes
            $("#selectedOption").empty().append('<option value="">Sélectionner une option</option>'); // Clear options
            $("#productStock").val(''); // Clear stock

            if (selectedDcId) {
                ApiService.fetchProductsByDistributionCenter(selectedDcId)
                    .done(function(response) {
                        if (response && response.data) {
                            allProducts = response.data; // Store all products
                            productSelect.prop("disabled", false); // Enable product select
                            response.data.forEach(function(product) {
                                productSelect.append(`<option value="${product.id}">${product.name}</option>`);
                            });
                        }
                    })
                    .fail(function(jqXHR) {
                        console.error("Error fetching products:", jqXHR.responseText);
                        productSelect.prop("disabled", true); // Keep disabled on error
                    });
            }
        });

        // Event listener for Product selection change
        $("#selectedProduct").on("change", function() {
            const selectedProductId = $(this).val();
            const productOptionContainer = $("#product-option-container");
            const selectedOptionSelect = $("#selectedOption");
            const productStockInput = $("#productStock");

            selectedOptionSelect.empty().append('<option value="">Sélectionner une option</option>');
            productOptionContainer.hide(); // Hide by default
            productStockInput.val(''); // Clear stock

            if (selectedProductId) {
                const selectedProduct = allProducts.find(p => p.id == selectedProductId);
                if (selectedProduct) {
                    productStockInput.val(selectedProduct.quantity); // Display stock

                    if (selectedProduct.type === 'bottle') {
                        productOptionContainer.show();
                        if (selectedProduct.options) {
                            selectedProduct.options.forEach(function(option) {
                                selectedOptionSelect.append(`<option value="${option.value}">${option.label} (${option.price} XAF)</option>`);
                            });
                        }
                    } else if (selectedProduct.type === 'accessory') {
                        // No specific options for accessories, just price
                        productOptionContainer.hide();
                    }
                }
            }
        });

        // Add Product to Cart button handler
        $("#addProductBtn").on("click", function() {
            const selectedProductId = $("#selectedProduct").val();
            const quantity = parseInt($("#productQuantity").val());
            const selectedOptionValue = $("#selectedOption").val();

            if (!selectedProductId || isNaN(quantity) || quantity <= 0) {
                alert("Veuillez sélectionner un produit et une quantité valide.");
                return;
            }

            const productToAdd = allProducts.find(p => p.id == selectedProductId);
            if (!productToAdd) {
                alert("Produit non trouvé.");
                return;
            }

            let itemPrice = 0;
            let optionName = '';

            if (productToAdd.type === 'bottle') {
                if (!selectedOptionValue) {
                    alert("Veuillez sélectionner une option pour la bouteille.");
                    return;
                }
                const selectedOption = productToAdd.options.find(opt => opt.value === selectedOptionValue);
                if (selectedOption) {
                    itemPrice = selectedOption.price;
                    optionName = selectedOption.label;
                } else {
                    alert("Option de bouteille invalide.");
                    return;
                }
            } else if (productToAdd.type === 'accessory') {
                itemPrice = productToAdd.price;
                optionName = 'N/A'; // No specific option for accessories
            }

            const newCartItem = {
                id: productToAdd.id,
                name: productToAdd.name,
                product_type: productToAdd.type,
                option_value: selectedOptionValue,
                option_name: optionName,
                price: itemPrice,
                quantity: quantity
            };

            CartService.addToCart(newCartItem);

            // Reset product selection fields after adding to cart
            $("#selectedProduct").val("").trigger('change');
            $("#productQuantity").val(1);
        });
    }

    return {
        init: init
    };
})();
