<div class="d-grid">
  <h6>Rincian Transaksi</h6>
  <dl class="row mb-0">
    @if (isset($paymentMethod))
      <dt class="col-sm-8 fw-normal">Metode Pembayaran</dt>
      <dd class="col-sm-4 text-md-end">{{ $paymentMethod }}</dd>
    @endif
    <dt class="col-sm-8 fw-normal">Total Harga (<span id="item-count">{{ isset($itemCount) ? $itemCount : 1 }}</span> Barang)</dt>
    <dd class="col-sm-4 text-md-end" id="cost-items">{{ isset($costItems) ? $costItems : '...' }}</dd>
    <dt class="col-sm-8 fw-normal">Biaya Pengiriman</dt>
    <dd class="col-sm-4 text-md-end" id="cost-shipping">{{ isset($costShipping) ? $costShipping : '...' }}</dd>
    <dt class="col-sm-8 fw-normal">Biaya Pemrosesan
      <i data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Biaya untuk memproses pembayaran">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-info-circle" viewBox="0 0 16 16">
          <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
          <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0"/>
        </svg>
      </i>
    </dt>
    <dd class="col-sm-4 text-md-end" id="cost-proccessing">{{ isset($costProcessing) ? $costProcessing : '...' }}</dd>
    <dt class="col-sm-8 fw-normal fw-bold">Total Tagihan</dt>
    <dd class="col-sm-4 text-md-end fw-bold" id="grand-total">{{ isset($grandTotal) ? $grandTotal : '...' }}</dd>
  </dl>
  {{ $slot }}
</div>