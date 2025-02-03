window.addEventListener('DOMContentLoaded', function(){
    document.querySelector("#addBtn").addEventListener('click', function(event){
        event.preventDefault();

        const num1 = document.querySelector("#num1").value;
        const num2 = document.querySelector("#num2").value;

        document.querySelector("#output").style.display = 'none';
        document.querySelector("#loader").style.display = 'block';

        fetch("/handleAjax", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
                num1: num1,
                num2: num2
            })
        })
            .then(response => response.text())
            .then(data => {
                console.log(data);
                document.querySelector("#output").innerHTML = data;
                document.querySelector("#loader").style.display = 'none';
                document.querySelector("#output").style.display = 'block';
            })
            .catch(error => {
                console.log('erreur, ', error)
            })
    })
})