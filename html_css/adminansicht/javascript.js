const allButtons = document.querySelectorAll('.button-red, .button-grey');

allButtons.forEach(button => {
    button.addEventListener('click', function() {
        const link = button.querySelector('a');
        if (link) {
            link.click();
        }
    });
});

/*
Anpassung der Statusfarben
Abhängig von dem Status wird eine andere css-Klasse angehängt
*/
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".status").forEach(function (el) {
        const status = el.textContent.trim();

        switch (status) {
            case "EINGEGANGEN":
                el.classList.add("status-gelb");
                break;
            case "ANGENOMMEN":
                el.classList.add("status-gruen");
                break;
            case "ABGELEHNT":
                el.classList.add("status-rot");
                break;
            case "NEUEINZUREICHEN":
                el.classList.add("status-orange");
                break;
        }
    });
});