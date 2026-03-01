const form = document.getElementById('feedbackForm');
const statusMessage = document.getElementById('statusMessage');
const submitBtn = document.getElementById('submitBtn');

function setError(id, message) {
    document.getElementById(id).textContent = message;
}

function validateName() {
    const value = document.getElementById('name').value.trim();
    const valid = value.length >= 2;
    setError('nameError', valid ? '' : 'Name must be at least 2 characters.');
    return valid;
}

function validateEmail() {
    const value = document.getElementById('email').value.trim();
    const valid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
    setError('emailError', valid ? '' : 'Enter a valid email.');
    return valid;
}

function validateDepartment() {
    const value = document.getElementById('department').value;
    const valid = value !== '';
    setError('departmentError', valid ? '' : 'Please select a department.');
    return valid;
}

function validateMessage() {
    const value = document.getElementById('message').value.trim();
    const valid = value.length >= 10;
    setError('messageError', valid ? '' : 'Feedback must be at least 10 characters.');
    return valid;
}

function validateAll() {
    const checks = [validateName(), validateEmail(), validateDepartment(), validateMessage()];
    return checks.every(Boolean);
}

function attachRealtimeValidation() {
    document.getElementById('name').addEventListener('keyup', validateName);
    document.getElementById('email').addEventListener('keyup', validateEmail);
    document.getElementById('department').addEventListener('keyup', validateDepartment);
    document.getElementById('department').addEventListener('change', validateDepartment);
    document.getElementById('message').addEventListener('keyup', validateMessage);
}

function attachHoverHighlights() {
    const fields = document.querySelectorAll('.interactive-field');
    fields.forEach((field) => {
        field.addEventListener('mouseenter', () => field.classList.add('hovered'));
        field.addEventListener('mouseleave', () => field.classList.remove('hovered'));
    });
}

submitBtn.addEventListener('dblclick', () => {
    const valid = validateAll();
    if (!valid) {
        statusMessage.textContent = 'Please fix errors before submitting.';
        statusMessage.className = 'status bad';
        return;
    }

    statusMessage.textContent = 'Feedback submitted successfully on double-click.';
    statusMessage.className = 'status ok';
    form.reset();
});

attachRealtimeValidation();
attachHoverHighlights();
