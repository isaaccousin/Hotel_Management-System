function loadDashboardData() {
    $.getJSON('../api/dashboard_data.php', function(data) {
        $('#booked_count').text(data.room_stats.booked);
        $('#available_count').text(data.room_stats.available);
        $('#maintenance_count').text(data.room_stats.maintenance);
        $('#occupancy_rate').text(data.occupancy + '%');

        $('#rev_day').text('$' + data.revenue.daily.toFixed(2));
        $('#rev_week').text('$' + data.revenue.weekly.toFixed(2));
        $('#rev_month').text('$' + data.revenue.monthly.toFixed(2));
        $('#rev_year').text('$' + data.revenue.yearly.toFixed(2));
        $('#rev_due').text('$' + data.revenue.balance_due.toFixed(2));

        let bookedList = data.booked_rooms.map(room => `
            <tr>
                <td>${room.room_number}</td>
                <td>${room.guest_name}</td>
                <td>${room.check_in_date}</td>
                <td>${room.check_out_date}</td>
            </tr>
        `).join('');
        $('#booked_table tbody').html(bookedList);

        let availableList = data.available_rooms.map(room => `
            <tr>
                <td>${room.room_number}</td>
                <td>${room.type}</td>
                <td>$${room.price}</td>
            </tr>
        `).join('');
        $('#available_table tbody').html(availableList);
    });
}

$(document).ready(function () {
    loadDashboardData();
    setInterval(loadDashboardData, 15000); // Refresh every 15 sec
});