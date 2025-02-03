import { Modal } from 'bootstrap';
import { post } from './ajax';

window.addEventListener('DOMContentLoaded', function () {
    const profileForm = document.getElementById('profile-form');
    const passwordForm = document.getElementById('passwordForm');
    const saveProfileBtn = document.querySelector('.save-profile');
    const updatePasswordBtn = document.querySelector('.update-password');
    const modal = new Modal(document.getElementById('updatePasswordModal'));

    async function handleFormSubmission(button, form, url, successMessage) {
        button.disabled = true;
        button.innerHTML = `
            <div class="spinner-border spinner-border-sm" role="status"></div> Processing...
        `;

        try {
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());
            const response = await post(url, data, form);

            if (response.ok) {
                alert(successMessage);
                if (url.includes('update-password')) modal.hide();
            }
        } catch (error) {
            console.error('Error:', error);
            alert('An error occurred');
        } finally {
            button.innerHTML = `<i class="bi bi-check2-circle me-1"></i> Save`;
            button.disabled = false;
        }
    }

    profileForm.addEventListener('submit', function (event) {
        event.preventDefault();
        handleFormSubmission(saveProfileBtn, profileForm, '/profile/update', 'Profile updated successfully');
    });

    passwordForm.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            updatePasswordBtn.click();
        }
    });
    updatePasswordBtn.addEventListener('click', function () {
        handleFormSubmission(updatePasswordBtn, passwordForm, '/profile/updatePassword', 'Password updated successfully');
    });
});
