const ajax = (url, method = 'get', data = {}, domElement = null) => {
    method = method.toLowerCase()

    let options = {
        method,
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    }

    const csrfMethods = new Set(['post', 'put', 'delete', 'patch'])

    if (csrfMethods.has(method)) {
        const additionalFields = {...getCsrfFields()}

        if(method !== 'post'){
            options.method = 'post'

            additionalFields._METHOD = method.toUpperCase()
        }

        if(data instanceof FormData){
            for (const additionalField in additionalFields){
                data.append(additionalField, additionalFields[additionalField])
            }

            delete options.headers['Content-Type']

            options.body = data
        } else {
            options.body = JSON.stringify({...data, ...additionalFields})
        }
    } else if (method === 'get') {
        url += '?' + (new URLSearchParams(data)).toString();
    }


    return fetch(url, options).then(response => {
        if(domElement){
            clearValidationErrors(domElement)
        }

        console.log(response)
        if(! response.ok){
            if(response.status === 422) {
                response.json().then(errors => handleValidationErrors(errors, domElement))
            } else if(response.status === 404) {
                alert(response.statusText)
            }
        }

            return response
        })
        .catch(error => {
            console.error("Erreur dans la requête Ajax:", error);
            throw error;
        });
};

function handleValidationErrors(errors, domElement){
    console.log(domElement)
    for(const name in errors){
        const element = domElement.querySelector(`input[name="${ name }"]`)

        element.classList.add('is-invalid');
        const errorDiv = document.createElement('div')

        console.log("ajoute l'erreur fils de pute")
        errorDiv.classList.add('invalid-feedback')
        errorDiv.textContent = errors[name][0]

        element.parentNode.append(errorDiv)
    }
}

function clearValidationErrors(domElement) {
    domElement.querySelectorAll(".is-invalid").forEach(element => {
        element.classList.remove('is-invalid')

        element.parentNode.querySelectorAll(".invalid-feedback").forEach(element => {
            element.remove()
        })
    })
}

const get  = (url, data) => ajax(url, 'get', data)
const post = (url, data, domElement) => ajax(url, 'post', data, domElement)
const del = (url, data) => ajax(url, 'delete', data)

function getCsrfFields() {
    const csrfNameField = document.querySelector('#csrfName');
    const csrfValueField = document.querySelector('#csrfValue');

    if (!csrfNameField || !csrfValueField) {
        console.warn('CSRF fields are missing from the DOM.');
        return {};
    }

    const csrfNameKey = csrfNameField.getAttribute('name');
    const csrfName = csrfNameField.content;
    const csrfValueKey = csrfValueField.getAttribute('name');
    const csrfValue = csrfValueField.content;

    return {
        [csrfNameKey]: csrfName,
        [csrfValueKey]: csrfValue
    };
}

export {
    ajax,
    get,
    post,
    del
}