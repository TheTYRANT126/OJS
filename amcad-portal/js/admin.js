/**
 * JavaScript para el panel de administración AMCAD
 */

// Variables globales
let currentType = null;
let currentId = null;

// Navegación entre secciones
document.addEventListener('DOMContentLoaded', function() {
    // Manejar navegación del sidebar
    const navItems = document.querySelectorAll('.sidebar-nav a[data-section]');
    navItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const section = this.dataset.section;
            if (section) {
                showSection(section);
            }
        });
    });

    // Manejar configuración de contacto
    const contactForm = document.getElementById('contact-config-form');
    if (contactForm) {
        contactForm.addEventListener('submit', handleContactConfigSubmit);
    }

    // Manejar modal de items
    const itemForm = document.getElementById('item-form');
    if (itemForm) {
        itemForm.addEventListener('submit', handleItemFormSubmit);
    }
});

// Mostrar sección
function showSection(sectionName) {
    // Actualizar navegación del sidebar
    document.querySelectorAll('.sidebar-nav li').forEach(item => {
        item.classList.remove('active');
    });
    document.querySelectorAll('.sidebar-nav a[data-section]').forEach(item => {
        if (item.dataset.section === sectionName) {
            item.parentElement.classList.add('active');
        }
    });

    // Mostrar sección correspondiente
    document.querySelectorAll('.admin-section').forEach(section => {
        section.classList.remove('active');
    });
    const targetSection = document.getElementById('section-' + sectionName);
    if (targetSection) {
        targetSection.classList.add('active');
    }
}

// Mostrar modal para agregar
function showAddModal(type) {
    currentType = type;
    currentId = null;

    const modal = document.getElementById('item-modal');
    const modalTitle = document.getElementById('modal-title');
    const itemForm = document.getElementById('item-form');
    const fileRequired = document.getElementById('file-required');
    const currentFile = document.getElementById('current-file');
    const archivoInput = document.getElementById('archivo');
    const currentFileEn = document.getElementById('current-file-en');
    const archivoEnInput = document.getElementById('archivo_en');

    // Reset form
    itemForm.reset();
    document.getElementById('item-type').value = type;
    document.getElementById('item-id').value = '';

    // Set title
    modalTitle.textContent = type === 'lineamiento' ? 'Agregar Lineamiento' : 'Agregar Recurso';

    // File is required for new items
    archivoInput.required = true;
    fileRequired.style.display = 'inline';
    currentFile.innerHTML = '';
    archivoEnInput.required = false;
    currentFileEn.innerHTML = '';

    // Clear messages
    document.getElementById('modal-message').innerHTML = '';

    modal.style.display = 'flex';
}

// Editar item
async function editItem(type, id) {
    currentType = type;
    currentId = id;

    try {
        // Obtener datos del item
        const response = await fetch(`api.php?action=get_${type}&id=${id}`);
        const result = await response.json();

        if (!result.success) {
            throw new Error(result.error);
        }

        const data = result.data;

        // Rellenar formulario
        const modal = document.getElementById('item-modal');
        const modalTitle = document.getElementById('modal-title');
        const fileRequired = document.getElementById('file-required');
        const currentFile = document.getElementById('current-file');
        const archivoInput = document.getElementById('archivo');
        const currentFileEn = document.getElementById('current-file-en');
        const archivoEnInput = document.getElementById('archivo_en');

        modalTitle.textContent = type === 'lineamiento' ? 'Editar Lineamiento' : 'Editar Recurso';
        document.getElementById('item-type').value = type;
        document.getElementById('item-id').value = id;
        document.getElementById('titulo').value = data.titulo;
        document.getElementById('descripcion').value = data.descripcion || '';
        document.getElementById('autor').value = data.autor || '';
        document.getElementById('titulo_en').value = data.titulo_en || '';
        document.getElementById('descripcion_en').value = data.descripcion_en || '';

        // File is optional for editing
        archivoInput.required = false;
        fileRequired.style.display = 'none';
        currentFile.innerHTML = `<p>Archivo actual: <a href="../uploads/${data.archivo}" target="_blank">${data.archivo}</a></p>`;
        archivoEnInput.required = false;
        if (data.archivo_en) {
            currentFileEn.innerHTML = `<p>Archivo en inglés: <a href="../uploads/${data.archivo_en}" target="_blank">${data.archivo_en}</a></p>`;
        } else {
            currentFileEn.innerHTML = '<p>No hay archivo en inglés cargado.</p>';
        }

        document.getElementById('modal-message').innerHTML = '';

        modal.style.display = 'flex';
    } catch (error) {
        alert('Error al cargar el item: ' + error.message);
    }
}

// Cerrar modal
function closeModal() {
    document.getElementById('item-modal').style.display = 'none';
}

// Manejar envío del formulario de items
async function handleItemFormSubmit(e) {
    e.preventDefault();

    const formData = new FormData(e.target);
    const type = formData.get('type');
    const id = formData.get('id');

    const action = id ? `edit_${type}` : `add_${type}`;
    formData.set('action', action);

    const messageDiv = document.getElementById('modal-message');
    messageDiv.innerHTML = '<p class="loading">Guardando...</p>';

    try {
        const response = await fetch('api.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (!result.success) {
            throw new Error(result.error);
        }

        messageDiv.innerHTML = `<p class="success">${result.message}</p>`;

        setTimeout(() => {
            closeModal();
            location.reload();
        }, 1000);
    } catch (error) {
        messageDiv.innerHTML = `<p class="error">Error: ${error.message}</p>`;
    }
}

// Eliminar item
async function deleteItem(type, id) {
    if (!confirm('¿Estás seguro de que deseas eliminar este elemento?')) {
        return;
    }

    try {
        const formData = new FormData();
        formData.set('action', `delete_${type}`);
        formData.set('id', id);

        const response = await fetch('api.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (!result.success) {
            throw new Error(result.error);
        }

        alert(result.message);
        location.reload();
    } catch (error) {
        alert('Error: ' + error.message);
    }
}

// Fijar/desfijar item
async function togglePin(type, id) {
    try {
        const formData = new FormData();
        formData.set('action', `toggle_pin_${type}`);
        formData.set('id', id);

        const response = await fetch('api.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (!result.success) {
            throw new Error(result.error);
        }

        location.reload();
    } catch (error) {
        alert('Error: ' + error.message);
    }
}

// Mover item hacia arriba
async function moveItemUp(type, id) {
    const list = document.getElementById(`${type}s-list`);
    const cards = Array.from(list.querySelectorAll('.item-card'));
    const currentIndex = cards.findIndex(card => parseInt(card.dataset.id) === id);

    if (currentIndex <= 0) return;

    // Intercambiar orden
    const newOrder = cards.map(card => parseInt(card.dataset.id));
    [newOrder[currentIndex], newOrder[currentIndex - 1]] = [newOrder[currentIndex - 1], newOrder[currentIndex]];

    await updateOrder(type, newOrder);
}

// Mover item hacia abajo
async function moveItemDown(type, id) {
    const list = document.getElementById(`${type}s-list`);
    const cards = Array.from(list.querySelectorAll('.item-card'));
    const currentIndex = cards.findIndex(card => parseInt(card.dataset.id) === id);

    if (currentIndex < 0 || currentIndex >= cards.length - 1) return;

    // Intercambiar orden
    const newOrder = cards.map(card => parseInt(card.dataset.id));
    [newOrder[currentIndex], newOrder[currentIndex + 1]] = [newOrder[currentIndex + 1], newOrder[currentIndex]];

    await updateOrder(type, newOrder);
}

// Actualizar orden
async function updateOrder(type, order) {
    try {
        const formData = new FormData();
        formData.set('action', `reorder_${type}s`);
        formData.set('order', JSON.stringify(order));

        const response = await fetch('api.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (!result.success) {
            throw new Error(result.error);
        }

        location.reload();
    } catch (error) {
        alert('Error: ' + error.message);
    }
}

// Manejar configuración de contacto
async function handleContactConfigSubmit(e) {
    e.preventDefault();

    const email = document.getElementById('contact_email').value;
    const messageDiv = document.getElementById('contact-config-message');

    messageDiv.innerHTML = '<p class="loading">Guardando...</p>';

    try {
        const formData = new FormData();
        formData.set('action', 'update_contact_email');
        formData.set('email', email);

        const response = await fetch('api.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (!result.success) {
            throw new Error(result.error);
        }

        messageDiv.innerHTML = `<p class="success">${result.message}</p>`;

        setTimeout(() => {
            messageDiv.innerHTML = '';
        }, 3000);
    } catch (error) {
        messageDiv.innerHTML = `<p class="error">Error: ${error.message}</p>`;
    }
}

// Cerrar modal al hacer clic fuera
window.onclick = function(event) {
    const modal = document.getElementById('item-modal');
    if (event.target === modal) {
        closeModal();
    }
}
