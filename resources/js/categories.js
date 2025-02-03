import { Modal }          from "bootstrap";
import { get, post, del } from "./ajax";
import Datatable          from "datatables.net"

window.addEventListener("DOMContentLoaded", function (){
    const editCategoryModal = new Modal(document.getElementById('editCategoryModal'))

    const table = new Datatable("#categoriesTable", {
        serverSide: true,
        ajax: "/categories/load",
        orderMulti: false,
        columns: [
            { data: "name" },
            { data: "createdAt" },
            { data: "updatedAt" },
            {
                data: null,
                sortable: false,
                render: function (data, type, row) {
                    return `
                    <div class="d-flex flex-">
                        <button type="submit" class="btn btn-outline-primary delete-category-btn" data-id="${row.id}">
                            <i class="bi bi-trash3-fill"></i>
                        </button>
                        <button class="ms2 btn btn-outline-primary edit-category-btn" data-id="${row.id}">
                            <i class="bi bi-pencil-fill"></i>
                        </button>
                    </div>
                `;
                }
            }
        ]
    });

    document.querySelector("#categoriesTable").addEventListener('click', function(event){
        const editBtn = event.target.closest('.edit-category-btn')
        const deleteBtn = event.target.closest('.delete-category-btn')

        if(editBtn){
            const categoryId = editBtn.getAttribute('data-id')

            get(`/categories/${categoryId}`)
                .then(response => response.json())
                .then(response => openEditCategoryModal(editCategoryModal, response))
        } else {
            const categoryId = deleteBtn.getAttribute('data-id')

            if(confirm("êtes-vous sûr de supprimer cette catégorie")){
                del(`/categories/${categoryId}`).then(response => {
                    if (response.ok){
                        table.draw()
                    }
                })
            }
        }

    })

    document.querySelector('.save-category-btn').addEventListener('click', function(event){
        const categoryId = event.currentTarget.getAttribute('data-id')
        const name = editCategoryModal._element.querySelector('input[name="name"]').value

        post(`/categories/${categoryId}`, {
            name: name
        }, editCategoryModal._element)
            .then(response => {
                if(response.ok){
                    table.draw();
                    editCategoryModal.hide()
                }
            })
            .catch(error => {
                console.log("Erreurs lors de la mise à jour de la catégorie: ", error)
            })
    })
})

function openEditCategoryModal(modal, {id, name}){
    const nameInput = modal._element.querySelector('input[name="name"]')
    nameInput.value = name

    modal._element.querySelector(".save-category-btn").setAttribute('data-id', id)

    modal.show()
}