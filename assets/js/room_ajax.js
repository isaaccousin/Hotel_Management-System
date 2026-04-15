// === FILE: room_ajax.js ===

function getApiPath(endpoint = 'room_action.php') {
  const pathParts = window.location.pathname.split('/');
  let depth = pathParts.length - 2; // Adjust if your structure is deeper or shallower
  let prefix = '';
  for (let i = 0; i < depth; i++) {
    prefix += '../';
  }
  return `${prefix}api/${endpoint}`;
}


let currentUserRole = sessionStorage.getItem('user_role') || 'staff';
const lang = JSON.parse(sessionStorage.getItem('room_lang')) || {};

$(document).ready(function () {
  loadRooms();
  loadDashboardStats(); // ✅ Load stats when page loads

  $('#searchAvailable').on('keyup', () => filterTable('availableTable', $('#searchAvailable').val()));
  $('#searchBooked').on('keyup', () => filterTable('bookedTable', $('#searchBooked').val()));
  $('#searchPending').on('keyup', () => filterTable('pendingTable', $('#searchPending').val()));
  $('#searchMaintenance').on('keyup', () => filterTable('maintenanceTable', $('#searchMaintenance').val()));
  $('#searchStorage').on('keyup', () => filterTable('storageTable', $('#searchStorage').val()));


  setInterval(loadRooms, 15000);
});


window.loadRooms = function loadRooms() {
  $.get('../api/load_rooms.php', function (res) {
    console.log("🔍 Rooms response from API:", res);

    if (res.status === 'success') {
      const available = [], booked = [], pending = [], maintenance = [], storage = [];

      res.rooms.forEach(room => {
        if (!room.id) {
          console.warn('Missing room ID:', room);
          return;
        }

        const isOverdue = room.status === 'booked' && room.check_out_date && new Date(room.check_out_date) < new Date();
        const checkoutInfo = room.check_out_date
          ? `<br><small class="${isOverdue ? 'text-danger fw-bold' : 'text-muted'}">
              Out: ${room.check_out_date} ${isOverdue ? '⚠️' : ''}
            </small>` : '';

        const balanceInfo = room.balance_due > 0
          ? `<br><small class="text-danger">Due: $${parseFloat(room.balance_due).toFixed(2)}</small>` : '';

        const row = `
          <tr>
            <td>${room.room_number}</td>
            <td>${room.type}</td>
            <td>
              $${parseFloat(room.price).toFixed(2)}
              ${balanceInfo}
            </td>
            <td>
              ${renderStatusBadge(room.status)}
              ${checkoutInfo}
            </td>
            <td>${generateActionButtons(room)}</td>
          </tr>
        `;

        if (room.status === 'available') available.push(row);
        if (room.status === 'booked' || room.status === 'pending_balance') {
  booked.push(row);
  if (parseFloat(room.balance_due) > 0) pending.push(row);
}

        if (room.status === 'maintenance') maintenance.push(row);
        if (room.status === 'storage') storage.push(row); 
      });

      $('#availableTable tbody').html(available.join(''));
      $('#bookedTable tbody').html(booked.join(''));
      $('#pendingTable tbody').html(pending.join(''));
      $('#maintenanceTable tbody').html(maintenance.join(''));
      $('#storageTable tbody').html(storage.join(''));
    } else {
      console.error('❌ Failed to load rooms:', res.message);
    }
  });
};


// Handles the Display of Room Status Change

function renderStatusBadge(status, balance_due = 0) {
  const isPending = parseFloat(balance_due) > 0 && status === 'booked';

  const statusMap = {
    'available': { label: lang.available || 'Available', color: 'success' },
    'booked': { label: lang.booked || 'Booked', color: 'primary' },
    'maintenance': { label: lang.maintenance || 'Maintenance', color: 'warning' }
  };

  if (isPending) {
    return `<span class="badge bg-danger text-uppercase">${lang.refund || 'Pending'}</span>`;
  }

  const { label, color } = statusMap[status] || { label: status, color: 'secondary' };
  return `<span class="badge bg-${color} text-uppercase">${label}</span>`;
}


function generateActionButtons(room) {
  const id = room.id;
  const actions = [];
  const hasBalance = parseFloat(room.balance_due) > 0;

  if (["admin", "manager"].includes(currentUserRole)) {
    // ✅ Show Payment button only if there's a balance due
    if (hasBalance) {
      actions.push(`<button class="btn btn-sm btn-success me-1 payment-btn" data-id="${id}">💳 ${lang.payment || 'Payment'}</button>`);
    }

    // ✅ Always show Edit button
    actions.push(`<button class="btn btn-sm btn-warning me-1 edit-btn" data-id="${id}">${lang.edit}</button>`);

    actions.push(`<button class="btn btn-sm btn-danger delete-btn" data-id="${id}">${lang.delete}</button>`);

    if (room.status === 'available') {
      actions.push(`<button class="btn btn-sm btn-secondary me-1 maintenance-btn" data-id="${id}">${lang.maintenance}</button>`);
    } else if (room.status === 'maintenance') {
      actions.push(`<button class="btn btn-sm btn-success me-1 restore-btn" data-id="${id}">${lang.restore}</button>`);
    }

    if (["booked", "pending_balance"].includes(room.status)) {
      actions.push(`<button class="btn btn-sm btn-info me-1 extend-btn" data-id="${id}">${lang.extend || 'Extend'}</button>`);
      actions.push(`<button class="btn btn-sm btn-danger me-1 refund-btn" data-id="${id}">${lang.refund || 'Refund'}</button>`);
    }
  }

  return actions.join(' ');
}



// === ACTION BINDINGS ===
$(document).on('click', '.delete-btn', function () {
  deleteRoom($(this).data('id'));
});

$(document).on('click', '.edit-btn', function () {
  editRoom($(this).data('id'));
});

$(document).on('click', '.maintenance-btn', function () {
  setToMaintenance($(this).data('id'));
});

$(document).on('click', '.restore-btn', function () {
  restoreRoom($(this).data('id'));
});

$(document).on('click', '.extend-btn', function () {
  extendStay($(this).data('id'));
});

$(document).on('click', '.refund-btn', function () {
  refundRoom($(this).data('id'));
});

$(document).on('click', '.payment-btn', function () {
  handleBalancePayment($(this).data('id'));
});


// === DELETE ROOM ===
function deleteRoom(id) {
  Swal.fire({
    title: 'Are you sure?',
    text: 'This room will be permanently deleted.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Yes, delete it!'
  }).then(result => {
    if (result.isConfirmed) {
      fetch('../api/room_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'delete', id })
      })
      .then(res => res.json())
      .then(data => {
        Swal.fire(data.status === 'success' ? 'Deleted!' : 'Error', data.message, data.status);
        if (data.status === 'success') loadRooms();
      });
    }
  });
}

// === EDIT ROOM ===
function editRoom(id) {
  $.get('../api/load_rooms.php', function (res) {
    if (res.status === 'success') {
      const room = res.rooms.find(r => r.id == id);
      if (!room) return Swal.fire('Error', 'Room not found.', 'error');

      Swal.fire({
        title: `${lang.edit} - ${room.room_number}`,
        html: `
          <input id="edit_type" class="form-control mb-2" value="${room.type}" placeholder="${lang.type}">
          <input id="edit_price" type="number" class="form-control" value="${room.price}" placeholder="${lang.price}">
        `,
        confirmButtonText: lang.submit || 'Submit',
        showCancelButton: true,
        focusConfirm: false,
        preConfirm: () => {
          const type = document.getElementById('edit_type').value.trim();
          const price = parseFloat(document.getElementById('edit_price').value);

          if (!type || isNaN(price) || price <= 0) {
            Swal.showValidationMessage('Please enter a valid type and price');
            return false;
          }

          return fetch('../api/room_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'edit', id, type, price })
          })
            .then(response => {
              if (!response.ok) {
                throw new Error('Server error');
              }
              return response.json();
            })
            .catch(() => {
              Swal.showValidationMessage('Network error. Please try again.');
            });
        }
      }).then(result => {
        if (result.isConfirmed && result.value) {
          if (result.value.status === 'success') {
  Swal.fire('Updated', result.value.message, 'success').then(() => {
    loadRooms();
    if (typeof loadDashboardStats === 'function') {
      loadDashboardStats(); // ✅ refresh revenue, balance, etc.
    }
  });
}
else {
            Swal.fire('Error', result.value.message || 'Update failed.', 'error');
          }
        }
      });
    } else {
      Swal.fire('Error', 'Failed to load room data.', 'error');
    }
  });
}




function loadDashboardStats() {
  fetch('../api/dashboard_data.php')
    .then(res => res.json())
    .then(data => {
      console.log("📊 Dashboard data:", data); // Debug: view raw data

      if (data.status === 'success') {
        const rev = data.revenue || {};
        const rooms = data.rooms || {};

        $('#dailyRevenue').text(`$${parseFloat(rev.daily || 0).toFixed(2)}`);
        $('#weeklyRevenue').text(`$${parseFloat(rev.weekly || 0).toFixed(2)}`);
        $('#monthlyRevenue').text(`$${parseFloat(rev.monthly || 0).toFixed(2)}`);
        $('#yearlyRevenue').text(`$${parseFloat(rev.yearly || 0).toFixed(2)}`);
        $('#balanceDue').text(`$${parseFloat(rev.balance_due || 0).toFixed(2)}`);

        $('#bookedRooms').text(rooms.booked || 0);
        $('#availableRooms').text(rooms.available || 0);
        $('#maintenanceRooms').text(rooms.maintenance || 0);
      } else {
        console.warn('Dashboard data error:', data.message);
      }
    })
    .catch(err => {
      console.error('Dashboard fetch failed:', err);
    });
}

// === REFUND ROOM ===
function refundRoom(id) {
  Swal.fire({
    title: lang.refund || 'Choose refund type',
    showDenyButton: true,
    showCancelButton: true,
    confirmButtonText: '75% Refund',
    denyButtonText: 'Partial Refund'
  }).then(result => {
    if (result.isConfirmed) {
      processRefund(id, 'refund');  // <-- use 'refund' instead of 'standard'
    } else if (result.isDenied) {
      Swal.fire({
        title: 'Enter amount to refund:',
        input: 'number',
        inputAttributes: { min: 0, step: 0.01 },
        showCancelButton: true
      }).then(input => {
        if (input.isConfirmed && input.value > 0) {
          processRefund(id, 'partial', parseFloat(input.value));
        } else if (input.isConfirmed) {
          Swal.fire('Invalid', 'Please enter a valid amount.', 'warning');
        }
      });
    }
  });
}


// === Process Refund ===
function processRefund(id, mode, amount = null) {
  const payload = {
    action: mode === 'partial' ? 'refund_partial' : 'refund',
    id: id
  };
  if (amount) payload.amount = amount;

  fetch('../api/room_action.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  })
  .then(async res => {
    const text = await res.text();
    try {
      const data = JSON.parse(text);
      if (data.status === 'success') {
        Swal.fire({
          icon: 'success',
          title: 'Success',
          text: data.message,
          confirmButtonText: 'OK'
        }).then(() => {
          if (data.receipt_id && !window.__printingReceipt) {
            window.__printingReceipt = true;
            const win = window.open(`../templates/receipts.php?id=${data.receipt_id}`, '_blank', 'width=800,height=600');
            setTimeout(() => window.__printingReceipt = false, 1000);
            if (!win) alert("Popup blocked! Please allow popups for this site.");
          }
          loadRooms();
        });
      } else {
        Swal.fire('Error', data.message, 'error');
      }
    } catch (e) {
      console.error('Invalid JSON from PHP:', text);
      Swal.fire('Error', 'Unexpected server response.', 'error');
    }
  })
  .catch(error => {
    console.error('Fetch error:', error);
    Swal.fire('Error', 'Request failed. Please try again.', 'error');
  });
}


// === EXTEND STAY ===
function extendStay(id) {
  fetch(`../api/load_booking.php?id=${id}&status=both`)
    .then(res => res.json())
    .then(data => {
      if (data.status !== 'success') return Swal.fire('Error', data.message, 'error');

      const { check_out_date, price } = data.booking;
      const t = {
        title: lang.extend_stay || 'Extend Stay',
        current_checkout: lang.current_checkout || 'Current checkout',
        pay_now: lang.pay_now || 'Pay Now',
        pay_later: lang.pay_later || 'Pay Later',
        submit: lang.submit || 'Submit',
        success: lang.success || 'Extended',
        error: lang.error || 'Error',
        invalid_date: lang.invalid_date || 'New date must be after current checkout',
        payment_option: lang.payment_option || 'Payment Option'
      };

      Swal.fire({
        title: t.title,
        html: `
          <p>${t.current_checkout}: <strong>${check_out_date}</strong></p>
          <input type="date" id="new_date" class="form-control mb-3" />
          <div class="mb-2"><strong>${t.payment_option}:</strong></div>
          <div class="form-check">
            <input class="form-check-input" type="radio" name="pay_option" id="pay_full" value="full" checked />
            <label class="form-check-label" for="pay_full">${t.pay_now}</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="radio" name="pay_option" id="pay_partial" value="partial" />
            <label class="form-check-label" for="pay_partial">Partial Payment</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="radio" name="pay_option" id="pay_later" value="later" />
            <label class="form-check-label" for="pay_later">${t.pay_later}</label>
          </div>
          <input type="number" id="partial_amount" class="form-control mt-2" placeholder="Enter partial amount" style="display:none;" min="0" />
        `,
        confirmButtonText: t.submit,
        showCancelButton: true,
        focusConfirm: false,
        didOpen: () => {
          $('input[name="pay_option"]').on('change', function () {
            $('#partial_amount').toggle($('#pay_partial').is(':checked'));
          });
        },
        preConfirm: () => {
          const newDate = document.getElementById('new_date').value;
          const paymentMode = $('input[name="pay_option"]:checked').val();
          const partialAmount = parseFloat($('#partial_amount').val()) || 0;

          if (!newDate || new Date(newDate) <= new Date(check_out_date)) {
            Swal.showValidationMessage(t.invalid_date);
            return false;
          }

          const start = new Date(check_out_date);
          const end = new Date(newDate);
          const msPerDay = 1000 * 60 * 60 * 24;
          const daysExtended = Math.ceil((end - start) / msPerDay);
          const extensionCost = daysExtended * price;

          let amountPaid = 0;
          if (paymentMode === 'partial') {
            if (isNaN(partialAmount) || partialAmount <= 0) {
              Swal.showValidationMessage('Please enter a valid partial amount');
              return false;
            }
            amountPaid = partialAmount;
          } else if (paymentMode === 'full') {
            amountPaid = extensionCost;
          } else if (paymentMode === 'later') {
            amountPaid = 0;
          }

          return fetch('../api/room_action.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    action: 'extend_date',
    id,
    new_date: newDate,
    price,
    payment_mode: paymentMode,
    amount_paid: amountPaid
  })
})
.then(async res => {
  if (!res.ok) throw new Error(`HTTP ${res.status}`);
  const data = await res.json();
  if (!data || typeof data.status === 'undefined') {
    throw new Error('Invalid response format');
  }
  return data;
})
.catch(err => {
  console.error("❌ Extend Stay Error:", err);
  Swal.showValidationMessage(`❌ Error: ${err.message}`);
  return false;
});


        }
      }).then(result => {
        if (result.isConfirmed && result.value.status === 'success') {
          Swal.fire(t.success, result.value.message, 'success').then(() => {
            if (result.value.receipt_id && !result.value.pay_later && !window.__printingReceipt) {
              window.__printingReceipt = true;
              const win = window.open(`../templates/receipts.php?id=${result.value.receipt_id}`, '_blank', 'width=800,height=600');
              setTimeout(() => window.__printingReceipt = false, 1000);
              if (!win) alert('Popup blocked! Please allow popups.');
            }
            loadRooms();
            if (typeof loadDashboardStats === 'function') loadDashboardStats();
          });
        } else if (result.isConfirmed) {
          Swal.fire(t.error, result.value.message, 'error');
        }
      });
    });
}


function handleBalancePayment(id) {
  fetch(`../api/load_booking.php?id=${id}&status=both`)
    .then(res => res.json())
    .then(data => {
      if (data.status !== 'success') return Swal.fire('Error', data.message, 'error');

      const { balance_due, amount_paid } = data.booking;
      const maxPayment = parseFloat(balance_due);

      Swal.fire({
        title: 'Make Payment',
        html: `
          <p>Outstanding Balance: <strong>$${maxPayment.toFixed(2)}</strong></p>
          <input type="number" id="payment_amount" class="form-control" min="0" max="${maxPayment}" step="0.01" placeholder="Enter payment amount">
        `,
        showCancelButton: true,
        confirmButtonText: 'Submit Payment',
        preConfirm: () => {
          const amount = parseFloat(document.getElementById('payment_amount').value);
          if (isNaN(amount) || amount <= 0 || amount > maxPayment) {
            Swal.showValidationMessage('Enter a valid amount not exceeding the balance.');
            return false;
          }

          return fetch('../api/room_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'pay_balance', id, amount_paid: amount })
          }).then(res => res.json());
        }
      }).then(result => {
        if (result.isConfirmed && result.value.status === 'success') {
          Swal.fire('Success', result.value.message, 'success').then(() => {
            if (result.value.receipt_id && !window.__printingReceipt) {
              window.__printingReceipt = true;
              window.location.href = `../templates/receipts.php?id=${result.value.receipt_id}&redirect=room_management.php`;

              setTimeout(() => window.__printingReceipt = false, 1000);
            }
            loadRooms();
            if (typeof loadDashboardStats === 'function') loadDashboardStats();
          });
        } else if (result.isConfirmed) {
          Swal.fire('Error', result.value.message, 'error');
        }
      });
    });
}



// === MAINTENANCE AND RESTORE ===
function setToMaintenance(id) {
  updateStatus(id, 'set_maintenance');
}

function restoreRoom(id) {
  updateStatus(id, 'restore');
}

function updateStatus(id, action) {
  fetch('../api/room_action.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action, id })
  })
  .then(res => res.json())
  .then(data => {
    Swal.fire(data.status === 'success' ? 'Updated' : 'Error', data.message, data.status);
    if (data.status === 'success') loadRooms();
  });
}

function submitRoomModal() {
  const room_number = $('#room_number').val().trim();
  const type = $('#room_type').val().trim();
  const price = parseFloat($('#room_price').val().trim());
  const amenities = $('#room_amenities').val().trim();
  const notes = $('#room_notes').val().trim();

  if (!room_number || !type || isNaN(price) || price <= 0) {
    Swal.fire('Error', 'Please fill all required fields with valid values.', 'error');
    return;
  }

  fetch('../api/room_action.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      action: 'add',
      room_number,
      type,
      price,
      amenities,
      notes
    })
  })
  .then(res => res.json())
  .then(data => {
    if (data.status === 'success') {
      Swal.fire('Success', data.message, 'success');
      $('#addRoomModal').modal('hide');
      loadRooms();
      if (typeof loadDashboardStats === 'function') loadDashboardStats();
    } else {
      Swal.fire('Error', data.message, 'error');
    }
  });
}


function filterTable(tableId, searchTerm) {
  const filter = searchTerm.trim().toLowerCase();

  $(`#${tableId} tbody tr`).each(function () {
    const row = $(this);
    const text = row.text().toLowerCase();

    // Match exact text
    let show = text.includes(filter);

    // Extra rule: if user types "overdue" or "expired", show only rows with "⚠️"
    if (filter === 'overdue' || filter === 'expired') {
      show = row.find('small.text-danger, small.fw-bold').text().includes('⚠️');
    }

    row.toggle(show);
  });
}