const allButtons = document.querySelectorAll('.button-red, .button-grey');

allButtons.forEach(button => {
    button.addEventListener('click', function() {
        const link = button.querySelector('a');
        if (link) {
            link.click();
        }
    });
});