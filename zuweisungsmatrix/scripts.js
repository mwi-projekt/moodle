
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
        const wunsche = wuensche.map(w => w.replace(/^\d\.\s*/, '').trim());

        let zugewiesen = false;
        for (let i = 0; i < wunsche.length; i++) {
            const hochschule = wunsche[i];
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

let matrixOpenSearchTimer = null;
let matrixOpenCurrentList = [];

function getMatrixOpenModalElements() {
    return {
        modal: document.getElementById('matrixOpenModal'),
        searchInput: document.getElementById('matrixSearchInput'),
        select: document.getElementById('matrixSelect'),
        status: document.getElementById('matrixOpenStatus')
    };
}

function setMatrixOpenStatus(message) {
    const { status } = getMatrixOpenModalElements();
    if (status) {
        status.textContent = message || '';
    }
}

function renderMatrixOptions(matrices) {
    const { select } = getMatrixOpenModalElements();
    if (!select) return;

    select.innerHTML = '';
    matrixOpenCurrentList = Array.isArray(matrices) ? matrices : [];

    if (matrixOpenCurrentList.length === 0) {
        const option = document.createElement('option');
        option.value = '';
        option.textContent = 'Keine passenden Matrizen gefunden';
        option.disabled = true;
        option.selected = true;
        select.appendChild(option);
        return;
    }

    matrixOpenCurrentList.forEach((matrix, index) => {
        const option = document.createElement('option');
        option.value = String(matrix.id);
        const dateText = formatMatrixTimestamp(matrix.timecreated);
        option.textContent = `${matrix.name}${dateText ? ` — ${dateText}` : ''} — ID ${matrix.id} — ${matrix.detailcount} Zuweisungen`;
        if (index === 0) {
            option.selected = true;
        }
        select.appendChild(option);
    });
}

async function loadMatrixList(search = '') {
    const params = new URLSearchParams({ action: 'list' });
    if (search.trim() !== '') {
        params.set('search', search.trim());
    }

    const response = await fetch(`load_matrix.php?${params.toString()}`);
    const result = await response.json();

    if (!response.ok || !result.success) {
        throw new Error(result.message || 'Unbekannter Fehler beim Laden der Matrizenliste.');
    }

    return result.matrices || [];
}

async function refreshMatrixOpenList(search = '') {
    setMatrixOpenStatus('Lade Matrizen...');

    try {
        const matrices = await loadMatrixList(search);
        renderMatrixOptions(matrices);

        if (matrices.length === 0) {
            setMatrixOpenStatus('Keine gespeicherten Matrizen gefunden.');
        } else {
            setMatrixOpenStatus(`${matrices.length} Matrizen gefunden.`);
        }
    } catch (error) {
        console.error('Fehler beim Laden der Matrizenliste:', error);
        renderMatrixOptions([]);
        setMatrixOpenStatus('Fehler beim Laden der Matrizenliste.');
    }
}

function openSavedMatrix() {
    const { modal, searchInput } = getMatrixOpenModalElements();
    if (!modal) return;

    modal.hidden = false;
    if (searchInput) {
        searchInput.value = '';
    }

    refreshMatrixOpenList('');
    if (searchInput) {
        searchInput.focus();
    }
}

function closeMatrixOpenModal() {
    const { modal } = getMatrixOpenModalElements();
    if (!modal) return;

    modal.hidden = true;
    setMatrixOpenStatus('');
}

async function confirmOpenSelectedMatrix() {
    const { select } = getMatrixOpenModalElements();
    if (!select || !select.value) {
        alert('Bitte zuerst eine Matrix auswählen.');
        return;
    }

    const masterid = parseInt(select.value, 10);
    if (Number.isNaN(masterid) || masterid <= 0) {
        alert('Bitte eine gültige Matrix auswählen.');
        return;
    }

    try {
        const loadResponse = await fetch(`load_matrix.php?action=load&masterid=${masterid}`);
        const loadResult = await loadResponse.json();

        if (!loadResponse.ok || !loadResult.success) {
            alert('Fehler beim Öffnen der Matrix: ' + (loadResult.message || 'Unbekannter Fehler.'));
            return;
        }

        const restoreResult = restoreMatrixDetails(loadResult.matrix.details || []);
        closeMatrixOpenModal();

        let message = `Matrix "${loadResult.matrix.name}" wurde geöffnet.`;
        if (restoreResult.missingStudents.length > 0) {
            message += `\nNicht gefundene Studierende: ${restoreResult.missingStudents.join(', ')}`;
        }
        if (restoreResult.missingUniversities.length > 0) {
            message += `\nNicht genügend freie Plätze für Hochschul-IDs: ${restoreResult.missingUniversities.join(', ')}`;
        }

        alert(message);
    } catch (error) {
        console.error('Fehler beim Öffnen der Matrix:', error);
        alert('Beim Öffnen der Matrix ist ein technischer Fehler aufgetreten.');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const { searchInput, select, modal } = getMatrixOpenModalElements();

    if (searchInput) {
        searchInput.addEventListener('input', () => {
            window.clearTimeout(matrixOpenSearchTimer);
            matrixOpenSearchTimer = window.setTimeout(() => {
                refreshMatrixOpenList(searchInput.value || '');
            }, 250);
        });

        searchInput.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeMatrixOpenModal();
            }
            if (event.key === 'Enter') {
                event.preventDefault();
                confirmOpenSelectedMatrix();
            }
        });
    }

    if (select) {
        select.addEventListener('dblclick', () => {
            confirmOpenSelectedMatrix();
        });
    }

    if (modal) {
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeMatrixOpenModal();
            }
        });
    }
});

function formatMatrixTimestamp(timestamp) {
    if (!timestamp) return '';
    try {
        return new Date(timestamp * 1000).toLocaleString('de-DE');
    } catch (error) {
        return '';
    }
}

function restoreMatrixDetails(details) {
    resetZuweisung();

    const studentMap = {};
    document.querySelectorAll('.student').forEach(student => {
        studentMap[String(student.dataset.studentid)] = student;
    });

    const cellsByUniversity = {};
    document.querySelectorAll('#matrix tbody td.drop-cell:not(.disabled)').forEach(cell => {
        const universityId = String(cell.dataset.universityid);
        if (!cellsByUniversity[universityId]) {
            cellsByUniversity[universityId] = [];
        }
        cellsByUniversity[universityId].push(cell);
    });

    const missingStudents = [];
    const missingUniversities = [];

    details.forEach(detail => {
        const student = studentMap[String(detail.studentid)];
        if (!student) {
            missingStudents.push(detail.studentid);
            return;
        }

        const targetCells = cellsByUniversity[String(detail.universityid)] || [];
        const targetCell = targetCells.find(cell => cell.children.length === 0);

        if (!targetCell) {
            missingUniversities.push(detail.universityid);
            return;
        }

        targetCell.appendChild(student);
    });

    toggleWunschAnzeige();
    updateEmptyInfoVisibility();

    return {
        missingStudents,
        missingUniversities
    };
}

// openSavedMatrix() wird jetzt als Dialog-Öffner verwendet.

/**
 * Liest die aktuell sichtbare Zuweisungsmatrix aus dem HTML aus
 * und erstellt daraus eine saubere Datenstruktur für die Datenbank.
 *
 * Ergebnis-Beispiel:
 * [
 *   {
 *     studentid: 5,
 *     universityid: 2,
 *     platz: 1
 *   },
 *   {
 *     studentid: 8,
 *     universityid: 3,
 *     platz: 2
 *   }
 * ]
 */
function collectMatrixData() {
    const details = [];

    document.querySelectorAll("#matrix tbody tr").forEach(row => {
        const cells = row.querySelectorAll("td.drop-cell");

        cells.forEach(cell => {
            if (cell.classList.contains("disabled")) return;

            const student = cell.querySelector(".student");
            if (!student) return;

            details.push({
                studentid: student.dataset.studentid,
                universityid: cell.dataset.universityid
            });
        });
    });

    return details;
}


/**
 * Sendet die aktuelle Matrix per AJAX an Moodle/PHP,
 * damit die Zuweisungen dauerhaft in der Datenbank gespeichert werden.
 */
async function saveMatrixToDatabase() {

    // Namen der Zuweisungsrunde abfragen
    const input = prompt(
        "Bitte Namen für die Zuweisungsrunde eingeben:",
        "Sommersemester 2026"
    );

    // Speichern abbrechen, wenn der Nutzer auf "Abbrechen" klickt
    if (input === null) {
        return;
    }

    // Leerzeichen am Anfang und Ende entfernen
    const matrixName = input.trim();

    // Leere Namen verhindern
    if (matrixName === "") {
        alert("Bitte einen gültigen Namen eingeben.");
        return;
    }

    // Aktuelle Matrix aus dem HTML auslesen
    const details = collectMatrixData();

    // Nicht speichern, wenn keine Zuweisungen vorhanden sind
    if (details.length === 0) {
        alert("Es gibt keine Zuweisungen zum Speichern.");
        return;
    }

    try {
        const response = await fetch("save_matrix.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                name: matrixName,
                details: details
            })
        });

        const result = await response.json();

        if (response.ok && result.success) {
            alert("Zuweisungsmatrix wurde gespeichert.");
        } else {
            alert("Fehler: " + (result.message || "Unbekannter Fehler."));
        }

    } catch (error) {
        console.error("Fehler beim Speichern:", error);
        alert("Beim Speichern ist ein technischer Fehler aufgetreten.");
    }
}