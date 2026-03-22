$(function () {
  $('#registerForm').on('submit', function (e) {
    e.preventDefault();
    $.post('includes/api.php?action=register', $(this).serialize())
      .done(() => window.location.href = 'dashboard.php')
      .fail((xhr) => App.toast(xhr.responseJSON?.message || 'Registration failed', 'danger'));
  });

  $('#loginForm').on('submit', function (e) {
    e.preventDefault();
    $.post('includes/api.php?action=login', $(this).serialize())
      .done(() => window.location.href = 'dashboard.php')
      .fail((xhr) => App.toast(xhr.responseJSON?.message || 'Login failed', 'danger'));
  });

  $('#adminLoginForm').on('submit', function(e){
    e.preventDefault();
    $.post('includes/api.php?action=admin_login', $(this).serialize())
      .done(() => location.reload())
      .fail((xhr) => App.toast(xhr.responseJSON?.message || 'Admin login failed', 'danger'));
  });
});
