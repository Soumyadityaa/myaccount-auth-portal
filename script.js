/**
 * script.js
 * Provides accessible client-side form validation and password visibility toggles.
 * Note: Server-side validation in PHP remains the authoritative security layer.
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Password Show / Hide Toggle Buttons
    const toggleButtons = document.querySelectorAll('.toggle-password');
    toggleButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const targetId = button.getAttribute('data-target');
            const input = document.getElementById(targetId);
            if (!input) return;

            const isPassword = input.getAttribute('type') === 'password';
            input.setAttribute('type', isPassword ? 'text' : 'password');
            button.textContent = isPassword ? 'Hide' : 'Show';
            button.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
        });
    });

    // Helper functions for inline field error display
    const setFieldError = (inputEl, errorElId, message) => {
        const errorEl = document.getElementById(errorElId);
        if (errorEl) {
            errorEl.textContent = message;
        }
        if (inputEl) {
            if (message) {
                inputEl.classList.add('input-invalid');
            } else {
                inputEl.classList.remove('input-invalid');
            }
        }
    };

    // 2. Registration Form Validation
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', (event) => {
            let isValid = true;

            const nameInput = document.getElementById('name');
            const usernameInput = document.getElementById('username');
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            const confirmInput = document.getElementById('confirm_password');

            const nameVal = nameInput.value.trim();
            const usernameVal = usernameInput.value.trim();
            const emailVal = emailInput.value.trim();
            const passwordVal = passwordInput.value;
            const confirmVal = confirmInput.value;

            // Validate Full Name
            if (nameVal.length < 2 || nameVal.length > 100) {
                setFieldError(nameInput, 'nameError', 'Full name must be between 2 and 100 characters.');
                isValid = false;
            } else {
                setFieldError(nameInput, 'nameError', '');
            }

            // Validate Username
            const usernamePattern = /^[a-zA-Z0-9_]{3,50}$/;
            if (!usernamePattern.test(usernameVal)) {
                setFieldError(
                    usernameInput,
                    'usernameError',
                    'Username must be 3–50 characters (letters, numbers, or underscores).'
                );
                isValid = false;
            } else {
                setFieldError(usernameInput, 'usernameError', '');
            }

            // Validate Email
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(emailVal)) {
                setFieldError(emailInput, 'emailError', 'Please enter a valid email address.');
                isValid = false;
            } else {
                setFieldError(emailInput, 'emailError', '');
            }

            // Validate Password Length
            if (passwordVal.length < 8) {
                setFieldError(passwordInput, 'passwordError', 'Password must be at least 8 characters long.');
                isValid = false;
            } else {
                setFieldError(passwordInput, 'passwordError', '');
            }

            // Validate Confirm Password Match
            if (confirmVal === '' || passwordVal !== confirmVal) {
                setFieldError(confirmInput, 'confirmPasswordError', 'Passwords do not match.');
                isValid = false;
            } else {
                setFieldError(confirmInput, 'confirmPasswordError', '');
            }

            if (!isValid) {
                event.preventDefault();
            }
        });
    }

    // 3. Login Form Validation
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', (event) => {
            let isValid = true;

            const identifierInput = document.getElementById('identifier');
            const passwordInput = document.getElementById('password');

            if (identifierInput.value.trim() === '') {
                setFieldError(identifierInput, 'identifierError', 'Please enter your username or email.');
                isValid = false;
            } else {
                setFieldError(identifierInput, 'identifierError', '');
            }

            if (passwordInput.value === '') {
                setFieldError(passwordInput, 'loginPasswordError', 'Please enter your password.');
                isValid = false;
            } else {
                setFieldError(passwordInput, 'loginPasswordError', '');
            }

            if (!isValid) {
                event.preventDefault();
            }
        });
    }
});