define([], function() {
    const init = function() {
        const tableBody = document.getElementById('learning-agreement-course-table-body');
        const addRowBtn = document.getElementById('la-add-row');
        const template = document.getElementById('learning-agreement-course-row-template');

        if (!tableBody || !addRowBtn || !template) {
            return;
        }

        const renumberRows = () => {
            const rows = tableBody.querySelectorAll('.la-course-row');
            rows.forEach((row, index) => {
                const numberCell = row.querySelector('.la-row-number');
                if (numberCell) {
                    numberCell.textContent = String(index + 1);
                }

                row.querySelectorAll('input, textarea, select').forEach((field) => {
                    field.name = field.name.replace(/course\[\d+\]/, `course[${index + 1}]`);
                });
            });
        };

        const addRow = () => {
            const nextIndex = tableBody.querySelectorAll('.la-course-row').length + 1;
            const html = template.innerHTML.replaceAll('__INDEX__', String(nextIndex));

            const tempWrapper = document.createElement('tbody');
            tempWrapper.innerHTML = html.trim();

            const newRow = tempWrapper.firstElementChild;
            if (newRow) {
                tableBody.appendChild(newRow);
                renumberRows();
            }
        };

        tableBody.addEventListener('click', function(e) {
            const deleteBtn = e.target.closest('.la-delete-row');
            if (!deleteBtn) {
                return;
            }

            const row = deleteBtn.closest('.la-course-row');
            if (row) {
                row.remove();
                renumberRows();
            }
        });

        addRowBtn.addEventListener('click', addRow);

        renumberRows();
    };

    return {
        init: init
    };
});
