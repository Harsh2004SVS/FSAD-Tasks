const form = document.getElementById('loginForm');
const emailInput = document.getElementById('email');
const passwordInput = document.getElementById('password');
const emailError = document.getElementById('emailError');
const passwordError = document.getElementById('passwordError');

function setError(node, message) {
    node.textContent = message;
}

function validateEmail() {
    const value = emailInput.value.trim();
    const valid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
    setError(emailError, valid ? '' : 'Please enter a valid email.');
    return valid;
}

function validatePassword() {
    const value = passwordInput.value;
    const valid = value.length >= 6;
    setError(passwordError, valid ? '' : 'Password must be at least 6 characters.');
    return valid;
}

emailInput.addEventListener('keyup', validateEmail);
passwordInput.addEventListener('keyup', validatePassword);

form.addEventListener('submit', (event) => {
    const emailOk = validateEmail();
    const passwordOk = validatePassword();

    if (!emailOk || !passwordOk) {
        event.preventDefault();
    }
});
