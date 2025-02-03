import "../css/auth.scss"

import {post} from './ajax'
import {Modal} from 'bootstrap'

window.addEventListener('DOMContentLoaded', function(){
    const modal = new Modal(document.getElementById('twoFactorAuthModal'))
    const togglePassword = document.querySelector(".toggle-password")
    const passwordInput = document.querySelector('.login-form input[name="password"]')

    togglePassword.addEventListener('click', () => {
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            togglePassword.style.color = '#28a745';
            togglePassword.querySelector('i').classList.remove('bi-eye-slash');
            togglePassword.querySelector('i').classList.add('bi-eye');
        } else {
            passwordInput.type = 'password';
            togglePassword.style.color = '#007bff';
            togglePassword.querySelector('i').classList.remove('bi-eye');
            togglePassword.querySelector('i').classList.add('bi-eye-slash');
        }
    });

    document.querySelector(".log-in-btn").addEventListener('click', function (event){
        const form = this.closest('form')
        const formData = new FormData(form)
        const inputs = Object.fromEntries(formData.entries())

        post(form.action, inputs, form).then(response => response.json()).then(response => {
            if(response.two_factor) {
                modal.show()
            } else {
                window.location = '/'
            }
        })
    })

    document.querySelector(".log-in-two-factor").addEventListener('click', function() {
        const code  = modal._element.querySelector('input[name="code"]').value
        const email = document.querySelector('.login-form input[name="email"]').value

        post('/login/two-factor', {email, code}, modal._element).then(response => {
            if(response.ok){
                console.log(response.session)
                window.location = '/'
            }
        })
    })
})