@component('components.modal', ['id' => 'confirmModal', 'title' => 'Konfirmasi'])
  <div class="modal-body">
    {{ $slot }}
  </div>
  <div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tidak</button>
    <button type="button" class="btn btn-danger" name="btn-confirm-modal">Ya</button>
  </div>
@endcomponent