class PasswordToggle {
    constructor() {
        this.passwordToggle = document.querySelector('.password-toggle');
        this.passwordInput = document.getElementById('password');
        this.toggleIcon = document.getElementById('toggleIcon');
        this.init();
    }

    init() {
        if (this.passwordToggle && this.passwordInput && this.toggleIcon) {
            this.passwordToggle.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.toggle();
            });
        }
    }

    toggle() {
        const isPassword = this.passwordInput.type === 'password';
        this.passwordInput.type = isPassword ? 'text' : 'password';
        this.toggleIcon.classList.toggle('iconoir-eye', !isPassword);
        this.toggleIcon.classList.toggle('iconoir-eye-closed', isPassword);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new PasswordToggle();
});
