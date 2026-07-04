let buttons = document.querySelectorAll(".toggle-btn");

buttons.forEach(function(button){

    button.addEventListener("click", function(){

        let details = this.nextElementSibling;

        if(details.style.display === "none"){

            details.style.display = "block";
            this.textContent = "Hide Details";

        }else{

            details.style.display = "none";
            this.textContent = "Show Details";

        }

    });

});