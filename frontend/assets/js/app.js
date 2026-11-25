const sidebar = document.querySelector('.sidebar');
const main = document.querySelector('.main');
const menu = document.getElementById('menu');

if (window.matchMedia('(max-width: 850px)').matches) {
  sidebar?.classList.add('collapsed');
  menu?.setAttribute('aria-expanded', 'false');
}

menu?.addEventListener('click', () => {
  const isMobile = window.matchMedia('(max-width: 850px)').matches;
  sidebar?.classList.toggle('collapsed');
  if (!isMobile) main?.classList.toggle('sidebar-collapsed');
  menu.setAttribute('aria-expanded', String(!sidebar?.classList.contains('collapsed')));
});

document.getElementById('globalSearch')?.addEventListener('input', e => {
  const query = e.target.value.toLowerCase();
  document.querySelectorAll('#records tbody tr').forEach(row => {
    row.style.display = row.innerText.toLowerCase().includes(query) ? '' : 'none';
  });
});

const recordModal = document.getElementById('recordModal');
recordModal?.addEventListener('show.bs.modal', event => {
  const form = recordModal.querySelector('form');
  const title = recordModal.querySelector('.modal-title');
  if (event.relatedTarget?.classList.contains('edit')) return;
  form.reset();
  form.querySelectorAll('select').forEach(field => { field.disabled = false; });
  form.querySelector('[name="id"]').value = '';
  title.textContent = `Add ${recordModal.dataset.recordTitle}`;
});

document.querySelectorAll('.edit').forEach(button => button.addEventListener('click', () => {
  const row = JSON.parse(button.dataset.row);
  const modal = recordModal;
  if (!modal) return;
  modal.querySelector('.modal-title').textContent = 'Edit Record';
  modal.querySelector('[name="id"]').value = row.id;
  modal.querySelectorAll('select').forEach(field => { field.disabled = false; });
  if (modal.querySelector('[name="password"]')) modal.querySelector('[name="password"]').value = '';
  if (modal.querySelector('[name="password_confirmation"]')) modal.querySelector('[name="password_confirmation"]').value = '';
  Object.entries(row).forEach(([key, value]) => {
    const field = modal.querySelector(`[name="${key}"]`);
    if (!field) return;
    const formattedValue = key === 'appointment_at' && value
      ? String(value).replace(' ', 'T').slice(0, 16)
      : value;
    field.value = formattedValue ?? '';
  });
  if (button.dataset.selfAccount === '1') {
    modal.querySelector('[name="role"]').disabled = true;
    modal.querySelector('[name="is_active"]').disabled = true;
  }
  new bootstrap.Modal(modal).show(button);
}));
