$(document).ready(function() {
    $("#login_button").click(function() {
        const login = $("#login").val();
        const password = $("#password").val();
        const loginStatus = $("#login_status");

        loginStatus.text("Connexion en cours...").removeClass("alert-danger alert-success").addClass("alert-warning");

        $.ajax({
            url: "/api/login",
            type: "POST",
            contentType: "application/json",
            data: JSON.stringify({
                login: login,
                password: password,
                device_name: "test-page"
            }),
            success: function(response) {
                // Log the full response for debugging
                console.log("Login Response:", response);

                if (response && response.data && response.data.access_token) {
                    localStorage.setItem('api_token', response.data.access_token);
                    loginStatus.text("Connexion réussie! Redirection...").removeClass("alert-warning").addClass("alert-success");
                    window.location.href = '/test-products/order.html';
                } else {
                    loginStatus.text("Échec de la connexion: Jeton non reçu. Vérifiez la console pour la réponse.").removeClass("alert-warning").addClass("alert-danger");
                }
            },
            error: function(jqXHR) {
                let errorMsg = "Erreur de connexion.";
                if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                    errorMsg += ` ${jqXHR.responseJSON.message}`;
                }
                loginStatus.text(errorMsg).removeClass("alert-warning").addClass("alert-danger");
                console.error("Login Error Response:", jqXHR.responseText);
            }
        });
    });
});
