function showToast(message, type = 'success') {
  let container = document.getElementById('toastContainer');

  // create container if missing
  if (!container) {
    container = document.createElement('div');
    container.id = 'toastContainer';
    document.body.appendChild(container);
  }

  const colors = {
    success: 'bg-success',
    error: 'bg-danger',
    warning: 'bg-warning text-dark',
    info: 'bg-primary'
  };

  const toast = document.createElement('div');
  toast.className = `custom-toast text-white ${colors[type] || 'bg-secondary'}`;

  toast.innerHTML = `
    <div class="toast-content">
      <span class="toast-message">${message}</span>
      <button class="toast-close">&times;</button>
    </div>
  `;

  container.appendChild(toast);

  setTimeout(() => toast.classList.add('show'), 50);

  toast.querySelector('.toast-close').onclick = () => hideToast(toast);

  setTimeout(() => hideToast(toast), 3000);
}

function hideToast(toast) {
  toast.classList.remove('show');
  toast.classList.add('hide');

  setTimeout(() => toast.remove(), 400);
}