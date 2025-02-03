import { Modal } from "bootstrap"
import { get, post, del } from "./ajax"
import Datatable from "datatables.net"

window.addEventListener("DOMContentLoaded", function (){
    const editModal   = new Modal(document.getElementById("editTransactionModal"))
    const createModal = new Modal(document.getElementById("newTransactionModal"))
    const uploadModal = new Modal(document.getElementById("uploadModal"))
    const uploadCsvModal = new Modal(document.getElementById("uploadCsvModal"))

    const table = new Datatable("#transactionsTable", {
        serverSide: true,
        ajax: "/transactions/load",
        orderMulti: false,
        rowCallback: function(row, data){
            console.log(row)
            console.log(data)

            if(data.wasReviewed){
                row.classList.add('fw-bold')
            }

            return row
        },
        columns: [
            { data: "description" },
            {
                data: row => new Intl.NumberFormat(
                    'en-US',
                    {
                        style: 'currency',
                        currency: 'USD',
                        currencySign: "accounting"
                    }
                ).format(row.amount)
            },
            { data: "category" },
            {
                data: row => {
                    let icons = []

                    for (let i = 0; i < row.receipts.length; i++) {
                        const receipt = row.receipts[i]

                        const span       = document.createElement('span')
                        const anchor     = document.createElement('a')
                        const icon       = document.createElement('i')
                        const deleteIcon = document.createElement('i')

                        deleteIcon.role = 'button'

                        span.classList.add('position-relative')
                        icon.classList.add('bi', 'bi-file-earmark-text', 'download-receipt', 'text-primary', 'fs-4')
                        deleteIcon.classList.add('bi', 'bi-x-circle-fill', 'delete-receipt', 'text-danger', 'position-absolute')

                        anchor.href   = `/transactions/${ row.id }/receipts/${ receipt.id }`
                        anchor.target = 'blank'
                        anchor.title  = receipt.name

                        deleteIcon.setAttribute('data-id', receipt.id)
                        deleteIcon.setAttribute('data-transactionId', row.id)

                        anchor.append(icon)
                        span.append(anchor)
                        span.append(deleteIcon)

                        icons.push(span.outerHTML)
                    }

                    return icons.join('')
                }
            },
            { data: "date" },
            {
                data: row => `
                    <div class="d-flex gap-2">
                        <div>
                            <i class="bi ${ row.wasReviewed ? "bi-check-circle-fill" : "bi-check-circle"} toggle-review-btn fs-4" role="button" data-id="${row.id}"></i>
                        </div>
                        
                        <div class="dropdown">
                            <i class="bi bi-tools fs-4" role="button" data-bs-toggle="dropdown"></i>
                            
                            <ul class="dropdown-menu">
                                <button class="dropdown-item edit-transaction-btn" href="#" data-id="${row.id}">
                                    <i class="bi bi-pencil-fill"></i> Edit
                                </button>
                                <button class="dropdown-item delete-transaction-btn" href="#" data-id="${row.id}">
                                    <i class="bi bi-trash3-fill"></i> Delete
                                </button>
                                <button class="dropdown-item open-receipt-upload-btn" href="#" data-id="${row.id}">
                                    <i class="bi bi-upload"></i> Upload
                                </button>
                            </ul>
                            
                        </div>
                        
                    </div>
                `,
                sortable: false
            }
        ]
    })

    document.querySelector("#transactionsTable").addEventListener('click', function(event){
        const editBtn = event.target.closest(".edit-transaction-btn")
        const deleteBtn = event.target.closest(".delete-transaction-btn")
        const uploadBtn = event.target.closest(".open-receipt-upload-btn")
        const deleteReceiptBtn = event.target.closest(".delete-receipt")
        const toggleReviewedBtn = event.target.closest(".toggle-review-btn")

        if(editBtn){
            const transactionId = editBtn.getAttribute('data-id')
            get(`/transactions/${transactionId}`)
                .then(response => response.json())
                .then(data => {

                    for(let name in data){
                        const nameInput = editModal._element.querySelector(`[name="${name}"]`)

                        if (nameInput) {
                            nameInput.value = data[name];
                        }
                    }

                    editModal._element.querySelector(".save-transaction-btn").setAttribute('data-id', data['id'])

                    editModal.show()
                })
        } else if(deleteBtn) {
            const transactionId = deleteBtn.getAttribute('data-id')
            if(confirm("Voulez-vous vraiment supprimer cette transaction")){
                del(`/transactions/${transactionId}`)
                    .then(response => {
                        if(response.ok){
                            table.draw()
                        }
                    })
            }
        } else if(uploadBtn){
            const transactionId = uploadBtn.getAttribute('data-id')

            uploadModal._element
                .querySelector(".upload-receipt-btn")
                .setAttribute('data-id', transactionId)

            uploadModal.show()
        } else if(deleteReceiptBtn){
            const receiptId = deleteReceiptBtn.getAttribute('data-id')
            const transactionId = deleteReceiptBtn.getAttribute('data-transactionId')

            del(`/transactions/${ transactionId }/receipts/${receiptId}`)
                .then(response => {
                    if(response.ok){
                        table.draw()
                    }
                })
        } else if(toggleReviewedBtn){
            if(toggleReviewedBtn.getAttribute('disabled') === 'true'){
                return;
            }

            const transactionId = toggleReviewedBtn.getAttribute('data-id')
            toggleReviewedBtn.style.cursor = "default"
            toggleReviewedBtn.setAttribute('disabled', 'true')

            post(`/transactions/${transactionId}/review`)
                .then(response => {
                    if(response.ok){
                        table.draw()
                        toggleReviewedBtn.style.cursor = ""
                        toggleReviewedBtn.removeAttribute('disabled')
                    }
                })
        }

    })

    document.querySelector(".create-transaction-btn").addEventListener('click', function(event){
        const data = getTransactionFromData(createModal)

        post('/transactions', data, createModal._element)
            .then(response => {
                if(response.ok){
                    table.draw()
                    createModal.hide()
                }
            })
    })

    document.querySelector('.upload-csv-btn').addEventListener('click', function(event){
        const button = event.currentTarget;
        button.setAttribute('disabled', true)
        const btnHtml = button.innerHTML

        button.innerHTML = `
            <div class="d-flex align-items-center justify-content-center gap-2">
                <div class="spinner-grow spinner-grow-sm text-light">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <span>Uploading ...</span>
            </div>
        `

        const formData = new FormData()
        const files = uploadCsvModal._element.querySelector('input[type="file"]').files

        for(let i = 0; i < files.length; i++){
            const file = files[i]
            formData.append('csvFile', file)
        }

        post('/transactions/import', formData, uploadCsvModal._element)
            .then(response => {
                button.removeAttribute('disabled')
                button.innerHTML = btnHtml

                if(response.ok){
                    table.draw()
                    uploadCsvModal.hide()
                }
            })
    })

    document.querySelector(".save-transaction-btn").addEventListener('click', function(event){
        const transactionId = event.currentTarget.getAttribute('data-id')
        post(`/transactions/${transactionId}`, getTransactionFromData(editModal), editModal._element)
            .then(response => {
                if(response.ok){
                    table.draw()
                    editModal.hide()
                }
            })
    })

    document.querySelector(".upload-receipt-btn").addEventListener('click', function(event){
        const transactionId = event.currentTarget.getAttribute('data-id')
        const formData = new FormData()
        const files = uploadModal._element.querySelector('input[type="file"]').files

        for(let i = 0; i < files.length; i++){
            formData.append('receipt', files[i])
        }

        post(`/transactions/${transactionId}/receipts`, formData, uploadModal._element)
            .then(response => {
                if(response.ok){
                    uploadModal.hide()
                    table.draw()
                }
            })

    })

})

function getTransactionFromData(modal){
    let data = {}

    const inputs = modal._element.getElementsByTagName('input')
    const selects = modal._element.getElementsByTagName('select')

    const fields = [
        ...inputs,
        ...selects
    ]

    fields.forEach(select => {
        data[select.name] = select.value
    })

    return data;
}