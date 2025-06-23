
function allowDrop(event){
    event.preventDefault();
}

function drop(event) {
    event.preventDefault();
    event.target.classList.remove("over");
    var id = event.dataTransfer.getData("text/plain");
    var dragged = document.getElementById(id);

    // Direktes Ziel
    let dropTarget = event.target;

    // Finde nächstgelegtes gültiges Drop-Ziel (Matrixzelle oder Studentenliste)
    while (dropTarget && dropTarget !== document && 
          !dropTarget.classList.contains('drop-cell') && 
          dropTarget.id !== 'studentList') {
        dropTarget = dropTarget.parentNode;
    }

    // Wenn nichts gefunden – abbrechen
    if (!dropTarget || dropTarget === document) return;

    // Drop in Matrixzelle (nur wenn leer)
    if (dropTarget.classList.contains('drop-cell')) {
        if (dropTarget.children.length === 0) {
            dropTarget.appendChild(dragged);
            // Wünsche ausblenden
            let wishes = dragged.querySelector('.wuensche');
            if (wishes) {
                const inMatrix = dropTarget.closest("#matrix");
                const showWishes = document.getElementById("showWishes")?.checked;
                wishes.style.display = inMatrix ? (showWishes ? 'block' : 'none') : 'block';
            }
        }
    }
    // Drop zurück in Studentenliste
    else if (dropTarget.id === 'studentList') {
        dropTarget.appendChild(dragged);
        // Wünsche einblenden
        let wishes = dragged.querySelector('.wuensche');
        if (wishes) {
            const showWishes = document.getElementById("showWishes")?.checked;
            wishes.style.display = showWishes ? 'block' : 'none';
        }
    }
    updateEmptyInfoVisibility();
}

function resetZuweisung() {
    const studentList = document.getElementById('studentList');

    // Alle .student Elemente im gesamten Dokument ermitteln
    const allStudents = document.querySelectorAll('.student');

    allStudents.forEach(student => {
        studentList.appendChild(student);

        // Wünsche wieder anzeigen
        const wishes = student.querySelector('.wuensche');
        if (wishes) {
            wishes.style.display = 'block';
        }
    });
    updateEmptyInfoVisibility();
}

function prepareExport() {
    const matrix = document.getElementById("matrix");
    const rows = matrix.querySelectorAll("tbody tr");
    const data = [];

    rows.forEach((row) => {
        const rowData = [];
        const cells = row.querySelectorAll("td:not(:first-child)"); // erste Spalte (Platznummer) ignorieren
        cells.forEach((cell) => {
            const student = cell.querySelector(".student");
            rowData.push(student ? student.innerText.trim() : "");
        });
        data.push(rowData);
    });

    document.getElementById("matrixdata").value = JSON.stringify(data);

    // Hochschulnamen aus Kopfzeile extrahieren
    const headers = document.querySelectorAll("#matrix thead th:not(:first-child)");
    const hochschulnamen = Array.from(headers).map(th => th.innerText.trim());
    document.getElementById("hochschulnamen").value = JSON.stringify(hochschulnamen);
}

function automatischZuteilen() {
    // Hochschulkapazitäten aus HTML-Tabelle ermitteln
    const headerCells = document.querySelectorAll("#matrix thead th:not(:first-child)");
    const hochschulNamen = Array.from(headerCells).map(th => th.innerText.trim());

    const kapazitaet = {};
    hochschulNamen.forEach((name, i) => {
        const colIndex = i + 1;
        const zellen = document.querySelectorAll(`#matrix tbody td:nth-child(${colIndex + 1})`);
        kapazitaet[name] = Array.from(zellen).filter(td => !td.classList.contains('disabled')).length;
    });

    const belegung = {};
    hochschulNamen.forEach(name => belegung[name] = 0);

    // Nur Studierende aus den Zellen entfernen, nicht alles löschen
    document.querySelectorAll("#matrix tbody tr").forEach(row => {
    const cells = row.querySelectorAll("td");
    for (let i = 1; i < cells.length; i++) {
        const td = cells[i];
        if (!td.classList.contains('disabled')) {
            const student = td.querySelector(".student");
            if (student) {
                document.getElementById("studentList").appendChild(student);

                // Wünsche wieder anzeigen
                const wishes = student.querySelector(".wuensche");
                if (wishes) wishes.style.display = 'block';
            }
        }
    }
});



    // Studierendenliste durchgehen
    const studenten = Array.from(document.querySelectorAll(".student"));
    studenten.forEach(student => {
        const wuensche = Array.from(student.querySelectorAll(".wuensche small"))[0].innerText.split('\n');
        const wünsche = wuensche.map(w => w.replace(/^\d\.\s*/, '').trim());

        let zugewiesen = false;
        for (let i = 0; i < wünsche.length; i++) {
            const hochschule = wünsche[i];
            if (!hochschulNamen.includes(hochschule)) continue;

            if (belegung[hochschule] < kapazitaet[hochschule]) {
                // Freies Feld finden und einfügen
                const spaltenIndex = hochschulNamen.indexOf(hochschule) + 1;
                const zellen = document.querySelectorAll(`#matrix tbody td:nth-child(${spaltenIndex + 1})`);
                for (let td of zellen) {
                    if (!td.classList.contains('disabled') && td.innerHTML.trim() === "") {
                        //td.appendChild(student);
                        const showWishes = document.getElementById("showWishes")?.checked;
                        // Originales Element einfügen
                        td.appendChild(student);

                        // Wünsche anzeigen oder ausblenden, je nach Checkbox
                        const wishes = student.querySelector(".wuensche");
                        if (wishes) {
                            wishes.style.display = showWishes ? 'block' : 'none';
                        }
                        belegung[hochschule]++;
                        zugewiesen = true;
                        break;
                    }
                }
                if (zugewiesen) break;
            }
        }
    });
    updateEmptyInfoVisibility();
}

function toggleWunschAnzeige() {
    const showWishes = document.getElementById("showWishes").checked;
    const studenten = document.querySelectorAll(".student");

    studenten.forEach(student => {
        const wishes = student.querySelector(".wuensche");
        if (!wishes) return;

        const parent = student.closest("td");
        const inMatrix = parent && parent.closest("#matrix");

        // In der Matrix folgt die Anzeige dem Checkbox-Zustand,
        // in der Bewerberliste (nicht in Matrix) immer anzeigen
        if (inMatrix) {
            wishes.style.display = showWishes ? 'block' : 'none';
        } else {
            wishes.style.display = 'block';
        }
    });
}


document.addEventListener("DOMContentLoaded", () => {
    const checkbox = document.getElementById("showWishes");
    if (checkbox) {
        checkbox.addEventListener("change", toggleWunschAnzeige);
        toggleWunschAnzeige(); // Initialzustand direkt anwenden
    }
});

function updateEmptyInfoVisibility() {
    const studentList = document.getElementById('studentList');
    const emptyInfo = document.getElementById('emptyInfo');

    const hasStudents = studentList.querySelectorAll('.student').length > 0;
    emptyInfo.style.display = hasStudents ? 'none' : 'block';
}