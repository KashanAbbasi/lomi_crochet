$(function () {
  if (!$('#dashboardRoot').length) return;
  $.getJSON('includes/api.php', { action: 'dashboard' }).done(({ user, orders }) => {
    $('#profileName').val(user.name || '');
    $('#profilePhone').val(user.phone || '');
    $('#profileAddress').val(user.address || '');
    $('#profileCity').val(user.city || '');
    $('#profileCountry').val(user.country || 'Pakistan');
    $('#welcomeUser').text(user.name);
    $('#orderHistory').html(orders.map(order => `<tr><td>${order.order_number}</td><td>${order.status}</td><td>${App.formatCurrency(order.total_amount)}</td><td>${new Date(order.created_at).toLocaleDateString()}</td></tr>`).join('') || '<tr><td colspan="4" class="text-center">No orders found.</td></tr>');
  });

  $('#profileForm').on('submit', function (e) {
    e.preventDefault();
    $.post('includes/api.php?action=profile_update', $(this).serialize())
      .done(() => App.toast('Profile updated.'))
      .fail(() => App.toast('Unable to update profile.', 'danger'));
  });
});
