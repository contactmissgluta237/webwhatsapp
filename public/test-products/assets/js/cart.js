window.CartService = (function() {
    let cart = [];

    function getCart() {
        return cart;
    }

    function addToCart(product) {
        // Logic to prevent duplicates can be added here later
        cart.push(product);
        render();
    }

    function removeFromCart(index) {
        cart.splice(index, 1);
        render();
    }

    function render() {
        const cartItemsContainer = $("#cart-items");
        const emptyCartMsg = $("#cart-empty-msg");
        cartItemsContainer.empty();

        if (cart.length === 0) {
            emptyCartMsg.show();
        } else {
            emptyCartMsg.hide();
            cart.forEach((item, index) => {
                const totalPrice = (item.price || 0) * (item.quantity || 1);
                const row = `
                    <tr>
                        <td>${item.name}</td>
                        <td>${item.option_name || 'N/A'}</td>
                        <td>${item.price || 0} XAF</td>
                        <td>${item.quantity || 1}</td>
                        <td>${totalPrice} XAF</td>
                        <td>
                            <button class="btn btn-sm btn-danger remove-item" data-index="${index}">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
                cartItemsContainer.append(row);
            });
        }
    }

    function init() {
        $("#cart-items").on("click", ".remove-item", function() {
            const index = $(this).data("index");
            removeFromCart(index);
        });
        render(); // Initial render
    }

    return {
        init,
        addToCart,
        getCart
    };
})();
