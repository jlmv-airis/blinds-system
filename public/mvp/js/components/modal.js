let overlay, modalBox;

export function initModal() {
  overlay = document.getElementById('modal-overlay');
  modalBox = document.getElementById('modal-box');
  overlay.addEventListener('click', (e) => {
    if (e.target === overlay) closeModal();
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && !overlay.hidden) closeModal();
  });
}

export function openModal(html) {
  modalBox.innerHTML = html;
  overlay.hidden = false;
}

export function closeModal() {
  overlay.hidden = true;
  modalBox.innerHTML = '';
}
