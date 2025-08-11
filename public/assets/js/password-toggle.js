$(function () {
    $(".toggle-password").on('click', function (e) {

        var parent = $(this).closest(".form-group");
        var password = parent.find(':password').eq(0);
        var pwd = password.length ? password : parent.find(':text').eq(0);

        if (pwd.attr('type') === "password") {
            pwd.attr('type', 'text');
            $(this).removeClass("fa-eye").addClass("fa-eye-slash");
        } else {
            pwd.attr('type', 'password');
            $(this).removeClass("fa-eye-slash").addClass("fa-eye");
        }
    });

});
