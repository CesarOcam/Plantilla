<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendario</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</head>

<body>
    <div class="d-flex justify-content-center align-items-center vh-100">
        <div class="row w-100 justify-content-center">
            <!-- Card de Meses -->
            <div class="col-md-5">
                <div class="card">
                    <div class="card-body">
                        <form id="monthsForm">
                            <div class="mb-3">
                                <label for="monthName" class="form-label">Nombre del mes</label>
                                <input type="text" class="form-control" id="monthName" placeholder="Escribe un mes">
                            </div>
                            <button type="button" class="btn btn-secondary w-100" id="addMonthBtn">Añadir mes</button>
                        </form>
                        <ul class="list-group mt-3" id="monthsList"></ul>
                    </div>
                </div>
            </div>

            <!-- Card de Días -->
            <div class="col-md-5">
                <div class="card">
                    <div class="card-body">
                        <form id="daysForm">
                            <div class="mb-3">
                                <label for="dayName" class="form-label">Nombre del día</label>
                                <input type="text" class="form-control" id="dayName" placeholder="Escribe un día">
                            </div>
                            <button type="button" class="btn btn-secondary w-100" id="addDayBtn">Añadir día</button>
                        </form>
                        <ul class="list-group mt-3" id="daysList"></ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        
        // ------------------- MANEJO DE MESES -------------------
        const addMonthBtn = document.getElementById('addMonthBtn');
        const monthNameInput = document.getElementById('monthName');
        const monthsList = document.getElementById('monthsList');

        addMonthBtn.addEventListener('click', () => {
            const monthName = monthNameInput.value.trim();
            if (monthsList.children.length >= 15) {
                alert("Solo puedes agregar un máximo de 15 meses.");
                return;
            }
            if (monthName === "") return alert("Escribe un nombre de mes");

            const li = document.createElement('li');
            li.className = "list-group-item d-flex justify-content-between align-items-center";
            li.textContent = monthName;

            const numDays = document.createElement('input');
            numDays.className = "form-control w-25 me-2 text-end";
            numDays.type = "number";
            numDays.value = 7;
            numDays.min = 1;
            numDays.max = 31;

            // Botón eliminar
            const deleteBtn = document.createElement('button');
            deleteBtn.className = "btn btn-sm btn-danger";
            deleteBtn.textContent = "Eliminar";
            deleteBtn.onclick = () => li.remove();

            li.appendChild(numDays);
            li.appendChild(deleteBtn);
            monthsList.appendChild(li);

            monthNameInput.value = ""; // limpiar input
        });

        // ------------------- MANEJO DE DÍAS -------------------
        const addDayBtn = document.getElementById('addDayBtn');
        const dayNameInput = document.getElementById('dayName');
        const daysList = document.getElementById('daysList');

        addDayBtn.addEventListener('click', () => {
            const dayName = dayNameInput.value.trim();
            if (daysList.children.length >= 31) {
                alert("Solo puedes agregar un máximo de 31 días.");
                return;
            }
            if (dayName === "") return alert("Escribe un nombre de día");

            const li = document.createElement('li');
            li.className = "list-group-item d-flex justify-content-between align-items-center";
            li.textContent = dayName;

            // Botón eliminar
            const deleteBtn = document.createElement('button');
            deleteBtn.className = "btn btn-sm btn-danger";
            deleteBtn.textContent = "Eliminar";
            deleteBtn.onclick = () => li.remove();

            li.appendChild(deleteBtn);
            daysList.appendChild(li);

            dayNameInput.value = ""; // limpiar input
        });
    </script>
</body>


</html>