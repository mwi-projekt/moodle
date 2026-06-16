
function allowDrop(event){
    event.preventDefault();
}

function drop(event) {
    event.preventDefault();
    event.target.classList.remove("over");
    const id = event.dataTransfer.getData("text/plain");
    const dragged = document.getElementById(id);

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
            markAsChanged();
            // ...existing code...
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
        markAsChanged();
        // ...existing code...
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
    markAsChanged();
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
    const ROUND_ID = "bewerbungsrunde-1";
    const NON_ASSIGN_COST = 1000000;
    const TIE_SCALE = 0.000001;

    const headerCells = document.querySelectorAll("#matrix thead th:not(:first-child)");
    const hochschulNamen = Array.from(headerCells).map(th => th.innerText.trim());

    const kapazitaet = {};
    hochschulNamen.forEach((name, i) => {
        const colIndex = i + 1;
        const zellen = document.querySelectorAll(`#matrix tbody td:nth-child(${colIndex + 1})`);
        kapazitaet[name] = Array.from(zellen).filter(td => !td.classList.contains("disabled")).length;
    });

    document.querySelectorAll("#matrix tbody tr").forEach(row => {
        const cells = row.querySelectorAll("td");
        for (let i = 1; i < cells.length; i++) {
            const td = cells[i];
            if (!td.classList.contains("disabled")) {
                const student = td.querySelector(".student");
                if (student) {
                    document.getElementById("studentList").appendChild(student);
                    const wishes = student.querySelector(".wuensche");
                    if (wishes) wishes.style.display = "block";
                }
            }
        }
    });

    const studenten = Array.from(document.querySelectorAll("#studentList .student"));

    function stableHash(str) {
        let h = 2166136261;
        for (let i = 0; i < str.length; i++) {
            h ^= str.charCodeAt(i);
            h = Math.imul(h, 16777619);
        }
        return (h >>> 0) / 4294967295;
    }

    function studentId(student, index) {
        return (
            student.dataset.id ||
            student.id ||
            student.querySelector(".name")?.innerText?.trim() ||
            student.innerText.trim() ||
            String(index)
        );
    }

    function getWunsche(student) {
        const text = student.querySelector(".wuensche small")?.innerText || "";
        return text
            .split("\n")
            .map(w => w.replace(/^\d\.\s*/, "").trim())
            .filter(Boolean);
    }

    const angebote = [];

    studenten.forEach((student, index) => {
        const id = studentId(student, index);
        const lottery = stableHash(`${id}|${ROUND_ID}`);
        const wunsche = getWunsche(student);

        wunsche.forEach((hochschule, rang) => {
            if (hochschulNamen.includes(hochschule)) {
                angebote.push({
                    student,
                    hochschule,
                    cost: rang + lottery * TIE_SCALE
                });
            }
        });

        angebote.push({
            student,
            hochschule: null,
            cost: NON_ASSIGN_COST + lottery * TIE_SCALE
        });
    });

    const assignedStudents = new Set();
    const belegung = {};
    hochschulNamen.forEach(name => belegung[name] = 0);

    angebote.sort((a, b) => a.cost - b.cost);

    for (const angebot of angebote) {
        if (assignedStudents.has(angebot.student)) continue;

        if (angebot.hochschule === null) {
            assignedStudents.add(angebot.student);
            continue;
        }

        if (belegung[angebot.hochschule] < kapazitaet[angebot.hochschule]) {
            const spaltenIndex = hochschulNamen.indexOf(angebot.hochschule) + 1;
            const zellen = document.querySelectorAll(`#matrix tbody td:nth-child(${spaltenIndex + 1})`);

            for (let td of zellen) {
                if (!td.classList.contains("disabled") && td.innerHTML.trim() === "") {
                    td.appendChild(angebot.student);

                    const showWishes = document.getElementById("showWishes")?.checked;
                    const wishes = angebot.student.querySelector(".wuensche");
                    if (wishes) wishes.style.display = showWishes ? "block" : "none";

                    belegung[angebot.hochschule]++;
                    assignedStudents.add(angebot.student);
                    break;
                }
            }
        }
    }

    markAsChanged();
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

     // Initialisiere Matrix-Name-Anzeige
     updateMatrixNameDisplay();
 });

function updateEmptyInfoVisibility() {
    const studentList = document.getElementById('studentList');
    const emptyInfo = document.getElementById('emptyInfo');

    const hasStudents = studentList.querySelectorAll('.student').length > 0;
    emptyInfo.style.display = hasStudents ? 'none' : 'block';
}

let matrixOpenSearchTimer = null;
 let matrixOpenCurrentList = [];
 let currentLoadedMatrixId = null;  // ID der derzeit geöffneten Matrix (null wenn neu)
 let currentLoadedMatrixName = null; // Name der derzeit geöffneten Matrix
 let hasUnsavedChanges = false;     // Flag für ungespeicherte Änderungen

function markAsChanged() {
    if (!hasUnsavedChanges) {
        hasUnsavedChanges = true;
        updateStatusDisplay();
    }
}

function updateStatusDisplay() {
     const statusEl = document.getElementById('matrixStatus');
     if (!statusEl) return;

     if (hasUnsavedChanges) {
         statusEl.textContent = '⚠ Ungespeicherte Änderungen';
         statusEl.style.color = '#e1001a';
         statusEl.style.fontWeight = 'bold';
     } else if (currentLoadedMatrixId) {
         statusEl.textContent = '✓ Matrix gespeichert';
         statusEl.style.color = '#0a8c0a';
         statusEl.style.fontWeight = 'normal';
     } else {
         statusEl.textContent = '';
     }
 }

 function updateMatrixNameDisplay() {
     const nameEl = document.getElementById('matrixNameDisplay');
     if (!nameEl) return;

     if (currentLoadedMatrixId && currentLoadedMatrixName) {
         nameEl.textContent = `Geöffnet: Matrix "${currentLoadedMatrixName}"`;
         nameEl.style.color = '#0a8c0a';
     } else {
         nameEl.textContent = 'Keine gespeicherte Matrix geöffnet';
         nameEl.style.color = '#666';
     }
 }

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
        const dateText = formatMatrixTimestamp(matrix.timemodified);
        option.textContent = dateText
            ? `${matrix.name} — gespeichert am: ${dateText}`
            : matrix.name;
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

    void refreshMatrixOpenList('');
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

         const restoreResult = restoreMatrixDetails(loadResult.matrix.details || [], masterid, loadResult.matrix.name);
         closeMatrixOpenModal();

         // Nur Fehler-Hinweise zeigen, keine Erfolgs-Meldung
         if (restoreResult.missingStudents.length > 0 || restoreResult.missingUniversities.length > 0) {
             let message = 'Warnung: Einige Einträge konnten nicht wiederhergestellt werden.';
             if (restoreResult.missingStudents.length > 0) {
                 message += `\nNicht gefundene Studierende: ${restoreResult.missingStudents.join(', ')}`;
             }
             if (restoreResult.missingUniversities.length > 0) {
                 message += `\nNicht genügend freie Plätze für Hochschul-IDs: ${restoreResult.missingUniversities.join(', ')}`;
             }
             alert(message);
         }
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
                 void refreshMatrixOpenList(searchInput.value || '');
             }, 250);
         });

         searchInput.addEventListener('keydown', (event) => {
             if (event.key === 'Escape') {
                 closeMatrixOpenModal();
             }
             if (event.key === 'Enter') {
                 event.preventDefault();
                 void confirmOpenSelectedMatrix();
             }
         });
     }

     if (select) {
         select.addEventListener('dblclick', () => {
             void confirmOpenSelectedMatrix();
         });
     }

     if (modal) {
         modal.addEventListener('click', (event) => {
             if (event.target === modal) {
                 closeMatrixOpenModal();
             }
         });
     }

     // Save Modal Event-Listener
     const saveModalElements = getMatrixSaveModalElements();
     if (saveModalElements.nameInput) {
         saveModalElements.nameInput.addEventListener('keydown', (event) => {
             if (event.key === 'Escape') {
                 closeMatrixSaveModal();
             }
             if (event.key === 'Enter') {
                 event.preventDefault();
                 void confirmSaveMatrixWithName();
             }
         });
     }

     if (saveModalElements.modal) {
         saveModalElements.modal.addEventListener('click', (event) => {
             if (event.target === saveModalElements.modal) {
                 closeMatrixSaveModal();
             }
         });
     }
 });

function formatMatrixTimestamp(timestamp) {
    if (!timestamp) return '';
    try {
        return new Intl.DateTimeFormat('de-DE', {
            day: 'numeric',
            month: 'numeric',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            hour12: false,
        }).format(new Date(timestamp * 1000));
    } catch (error) {
        return '';
    }
}

function restoreMatrixDetails(details, masterid = null, matrixName = null) {
     resetZuweisung();

     // Setze die Matrix-ID und Name wenn eine geladen wurde
     if (masterid) {
         currentLoadedMatrixId = masterid;
         currentLoadedMatrixName = matrixName || null;
     }

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

     // Nach dem Laden: keine ungespeicherten Änderungen
     hasUnsavedChanges = false;
     updateStatusDisplay();
     updateMatrixNameDisplay();

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


function getMatrixSaveModalElements() {
     return {
         modal: document.getElementById('matrixSaveModal'),
         nameInput: document.getElementById('matrixSaveNameInput'),
         status: document.getElementById('matrixSaveStatus')
     };
 }

 function setMatrixSaveStatus(message) {
     const { status } = getMatrixSaveModalElements();
     if (status) {
         status.textContent = message || '';
     }
 }

 function openMatrixSaveModal() {
     const { modal, nameInput } = getMatrixSaveModalElements();
     if (!modal) return;

     modal.hidden = false;
     setMatrixSaveStatus('');

     if (nameInput) {
         nameInput.value = '';
         nameInput.focus();
     }
 }

 function closeMatrixSaveModal() {
     const { modal } = getMatrixSaveModalElements();
     if (!modal) return;

     modal.hidden = true;
     setMatrixSaveStatus('');
 }

/**
 * Sendet die aktuelle Matrix per AJAX an Moodle/PHP,
 * damit die Zuweisungen dauerhaft in der Datenbank gespeichert werden.
 *
 * Falls currentLoadedMatrixId gesetzt: UPDATE des bestehenden Eintrags
 * Falls currentLoadedMatrixId null: Modal für Namensingabe öffnen
 */
function saveMatrixToDatabase() {
     const details = collectMatrixData();

     // Nicht speichern, wenn keine Zuweisungen vorhanden sind
     if (details.length === 0) {
         alert("Es gibt keine Zuweisungen zum Speichern.");
         return;
     }

     // Wenn Matrix geladen: direkt Update ohne Dialog
     if (currentLoadedMatrixId) {
         performMatrixSave(null, currentLoadedMatrixId, details);
     } else {
         // Neue Matrix: Modal für Namen öffnen
         openMatrixSaveModal();
     }
 }

 async function confirmSaveMatrixWithName() {
     const { nameInput } = getMatrixSaveModalElements();
     if (!nameInput) return;

     const matrixName = nameInput.value.trim();

     // Leere Namen verhindern
     if (matrixName === "") {
         alert("Bitte einen gültigen Namen eingeben.");
         return;
     }

     const details = collectMatrixData();
     closeMatrixSaveModal();

     await performMatrixSave(matrixName, null, details);
 }

 async function performMatrixSave(matrixName, masterid, details) {
     try {
         const response = await fetch("save_matrix.php", {
             method: "POST",
             headers: {
                 "Content-Type": "application/json"
             },
             body: JSON.stringify({
                 name: matrixName,
                 masterid: masterid,
                 details: details
             })
         });

         const result = await response.json();

         if (response.ok && result.success) {
             // Bei erfolgreicher Speicherung (INSERT): die neue ID speichern
             if (result.masterid && !masterid) {
                 currentLoadedMatrixId = result.masterid;
                 currentLoadedMatrixName = matrixName;
             }

             hasUnsavedChanges = false;
             updateStatusDisplay();
             updateMatrixNameDisplay();

             // Keine Erfolgs-Alert, nur Status-Update
         } else {
             alert("Fehler: " + (result.message || "Unbekannter Fehler."));
         }

     } catch (error) {
         console.error("Fehler beim Speichern:", error);
         alert("Beim Speichern ist ein technischer Fehler aufgetreten.");
     }
 }
