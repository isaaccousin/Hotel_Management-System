// === FILE: room_ajax.js ===

let currentUserRole = sessionStorage.getItem('user_role') || 'admin';
const lang = JSON.parse(sessionStorage.getItem('room_lang')) || {};

$(document).ready(function () {
  loadRooms();
  $('#searchAvailable').on('keyup', () => filterTable('availableTable', $('#searchAvailable').val()));
  $('#searchBooked').on('keyup', () => filterTable('bookedTable', $('#searchBooked').val()));
  $('#searchPending').on('keyup', () => filterTable('pendingTable', $('#searchPending').val()));
  $('#searchMaintenance').on('keyup', () => filterTable('maintenanceTable', $('#searchMaintenance').val()));
  setInterval(loadRooms, 15000);
});

function loadRooms() {
  $.get('../api/load_rooms.php', function (res) {
    if (res.status === 'success') {
      const available = [], booked = [], pending = [], maintenance = [];
      res.rooms.forEach(room => {
        const row = `
          <tr>
            <td>${room.room_number}</td>
            <td>${room.type}</td>
            <td>$${parseFloat(room.price).toFixed(2)}</td>
            <td>${getStatusBadge(room.status)}</td>
            <td>${renderActions(room)}</td>
          </tr>`;
        if (room.status === 'available') available.push(row);
        if (room.status === 'booked') booked.push(row);
        if (room.status === 'pending_balance') pending.push(row);
        if (room.status === 'maintenance') maintenance.push(row);
      });
      $('#availableTable tbody').html(available.join(''));
      $('#bookedTable tbody').html(booked.join(''));
      $('#pendingTable tbody').html(pending.join(''));
      $('#maintenanceTable tbody').html(maintenance.join(''));
    }
  });
}

function renderActions(room) {
  const id = room.id;
  const status = room.status;
  let actions = '';

  if (['admin', 'manager'].includes(currentUserRole)) {
    actions += `<button class="btn btn-sm btn-dark" onclick="editRoom(${id})">Edit</button> `;
    if (currentUserRole === 'admin') {
      actions += `<button class="btn btn-sm btn-danger" onclick="handleAction(${id}, 'delete')">Delete</button> `;
    }
  }

  if (status === 'available') {
    actions += `<button class="btn btn-sm btn-warning" onclick="handleAction(${id}, 'set_booked')">Book</button> `;
    actions += `<button class="btn btn-sm btn-secondary" onclick="handleAction(${id}, 'set_maintenance')">Maintenance</button> `;
  } else if (status === 'booked') {
   actions += `<button class="btn btn-sm btn-success" onclick="extendStay(${id})">Extend</button> `;

    actions += `<button class="btn btn-sm btn-info" onclick="handleAction(${id}, 'checkout')">Checkout</button> `;
    actions += `<button class="btn btn-sm btn-outline-primary" onclick="handleAction(${id}, 'transfer')">Transfer</button> `;
  } else if (status === 'maintenance') {
    actions += `<button class="btn btn-sm btn-primary" onclick="handleAction(${id}, 'restore')">Restore</button> `;
  } else if (status === 'pending_balance') {
    actions += `<button class="btn btn-sm btn-outline-success" onclick="handleAction(${id}, 'collect')">Collect Balance</button> `;
  }

  return actions;
}

function getStatusBadge(status) {
  const map = {
    'available': 'badge bg-success',
    'booked': 'badge bg-warning text-dark',
    'maintenance': 'badge bg-danger',
    'pending_balance': 'badge bg-info text-dark'
  };
  return `<span class="${map[status] || 'badge bg-secondary'}">${status.replace('_', ' ')}</span>`;
}

//Add Room

function addRoom() {
  Swal.fire({
    title: lang.add_room,
    html: `
      <input type="text" id="room_number" class="form-control mb-2" placeholder="${lang.room_number}">
      <input type="text" id="type" class="form-control mb-2" placeholder="${lang.type}">
      <input type="number" id="price" class="form-control mb-2" placeholder="${lang.price}">
      <input type="text" id="amenities" class="form-control mb-2" placeholder="WiFi, TV">
      <textarea id="notes" class="form-control mb-2" placeholder="Additional notes..."></textarea>
    `,
    confirmButtonText: lang.submit,
    focusConfirm: false,
    preConfirm: () => {
      const room_number = $('#room_number').val();
      const type = $('#type').val();
      const price = $('#price').val();
      const amenities = $('#amenities').val();
      const notes = $('#notes').val();

      if (!room_number || !type || !price) {
        Swal.showValidationMessage('All fields required');
        return false;
      }

      return fetch('../api/room_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'add', room_number, type, price, amenities, notes })
      }).then(res => res.json());
    }
  }).then(result => {
    if (result.isConfirmed) {
      if (result.status === 'success') {
        Swal.fire('Success', result.message || 'Room added successfully.', 'success');
        loadRooms();
        if (window.parent && window.parent.loadDashboardStats) {
          window.parent.loadDashboardStats();
        }
      } else {
        Swal.fire('Error', result.message || 'Failed to add room.', 'error');
      }
    }
  });
}


// Edit Room
function editRoom(id) {
  Swal.fire({
    title: 'Edit Room',
    html: `
      <input type="text" id="new_type" class="form-control mb-2" placeholder="${lang.type}">
      <input type="number" id="new_price" class="form-control mb-2" placeholder="${lang.price}">
    `,
    confirmButtonText: lang.submit,
    focusConfirm: false,
    preConfirm: () => {
      const type = $('#new_type').val();
      const price = $('#new_price').val();
      if (!type || !price) {
        Swal.showValidationMessage('Both fields required');
        return false;
      }
      return fetch('../api/room_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'edit', id, type, price })
      }).then(res => res.json());
    }
  }).then(result => {
    if (result.isConfirmed && result.status === 'success') loadRooms();
  });
}

// Extend Stay
function extendStay(id) {
  Swal.fire({
    title: 'Extend Stay',
    html: `<input type="date" id="new_date" class="form-control" required>`,
    confirmButtonText: 'Submit',
    showCancelButton: true,
    preConfirm: () => {
      const newDate = $('#new_date').val();
      if (!newDate) {
        Swal.showValidationMessage('Please enter a new checkout date');
        return false;
      }

      // Get price dynamically (if not available you may pass it via renderActions)
      return fetch('../api/load_rooms.php')
        .then(res => res.json())
        .then(result => {
          const room = result.rooms.find(r => r.id == id);
          if (!room) throw new Error("Room not found.");
          return fetch('../api/room_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              action: 'extend_date',
              id,
              new_date: newDate,
              price: room.price
            })
          }).then(res => res.json());
        });
    }
  }).then(result => {
    if (result.isConfirmed && result.value?.status === 'success') {
      Swal.fire('Extended', result.value.message, 'success');
      loadRooms();
    } else if (result.value?.status === 'error') {
      Swal.fire('Error', result.value.message, 'error');
    }
  });
}



function handleAction(id, action) {
  fetch('../api/room_action.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action, id })
  }).then(res => res.json())
    .then(data => {
      if (data.status === 'success') {
        loadRooms();
        if (window.parent && window.parent.loadDashboardStats) {
          window.parent.loadDashboardStats();
        }
      } else {
        Swal.fire('Error', data.message, 'error');
      }
    });
}

function filterTable(tableId, query) {
  query = query.toLowerCase();
  $(`#${tableId} tbody tr`).each(function () {
    const rowText = $(this).text().toLowerCase();
    $(this).toggle(rowText.includes(query));
  });
}

function exportTable(tableId) {
  const table = document.getElementById(tableId);
  let csv = [];
  for (let row of table.rows) {
    let rowData = [];
    for (let cell of row.cells) {
      rowData.push(cell.innerText.replace(/,/g, ''));
    }
    csv.push(rowData.join(","));
  }
  const csvFile = new Blob([csv.join("\n")], { type: "text/csv" });
  const downloadLink = document.createElement("a");
  downloadLink.download = `${tableId}.csv`;
  downloadLink.href = window.URL.createObjectURL(csvFile);
  downloadLink.style.display = "none";
  document.body.appendChild(downloadLink);
  downloadLink.click();
  document.body.removeChild(downloadLink);
}