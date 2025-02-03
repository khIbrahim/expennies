import {post} from "./ajax";

window.addEventListener('DOMContentLoaded', function(){
    const forgotPasswordForm = document.querySelector(".forgot-password-form")
    const resetPasswordBtn = document.querySelector(".reset-password-btn")

    if(resetPasswordBtn){
        resetPasswordBtn.addEventListener('click', (event) => {
            const form = document.querySelector(".reset-password-form")
            const formData = new FormData(form)
            const data = Object.fromEntries(formData.entries())

            post(form.action, data, form).then(response => {
                if(response.ok){
                    alert('Password has been updated successfully!')

                    window.location = '/'
                }
            })
        })
    }

    forgotPasswordForm.addEventListener('submit', function(event){
        event.preventDefault()

        const formData = new FormData(forgotPasswordForm)
        const data = Object.fromEntries(formData.entries())

        post('/forgot-password', data, forgotPasswordForm).then(response => response.json()).then(response => {
            if(response.sent){
                alert("Un email vous a été envoyé")
            }
        })
    })
})