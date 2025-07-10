document.getElementById('btn_pagar').addEventListener('click', function() {
    const checkboxes = document.querySelectorAll('.kardex-checkbox:checked');

    if (checkboxes.length === 0) {
        console.log("No se seleccionó ningún Kardex");
        return;
    }

    checkboxes.forEach(cb => {
        const id = cb.value;
        console.log(`Kardex: ${id} pagado`);
    });
});

