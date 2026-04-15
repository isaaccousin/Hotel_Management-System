setInterval(() => {
  fetch('../auth/session_check.php', {
    method: 'GET',
    headers: {
      'X-Requested-With': 'XMLHttpRequest'
    }
  })
  .then(response => response.json())
  .then(data => {
    if (data.expired) {
      Swal.fire({
        icon: 'warning',
        title: 'Session Expired',
        text: data.message || 'Redirecting to login...',
        showConfirmButton: false,
        timer: 3000,
        allowOutsideClick: false,
        allowEscapeKey: false,
        didClose: () => {
          // Redirect to logout.php for a clean session destroy
          window.location.href = '../auth/logout.php?reason=timeout';
        }
      });
    }
  })
  .catch(error => {
    console.error('Session check failed:', error);
  });
}, 60000); // every 60 seconds