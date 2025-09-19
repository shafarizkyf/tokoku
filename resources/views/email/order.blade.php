<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Order Receipt</title>

<!-- Prevent Gmail from adding large text sizes -->
<style>
  /* General resets */
  body { margin:0; padding:0; -webkit-text-size-adjust:100%; -ms-text-size-adjust:100%; }
  table { border-collapse: collapse; mso-table-lspace:0pt; mso-table-rspace:0pt; }
  img { border:0; height:auto; line-height:100%; outline:none; text-decoration:none; display:block; }
  a { color: inherit; text-decoration: none; }

  /* Container */
  .email-wrapper { width:100%; background-color:#f4f6f8; padding:20px 0; }

  /* Card */
  .email-body { width:100%; max-width:680px; margin:0 auto; background:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 2px 6px rgba(0,0,0,0.06); }
  .email-inner { padding:28px; font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial; color:#334155; font-size:15px; line-height:1.45; }

  /* Header */
  .brand { display:flex; align-items:center; gap:12px; }
  .brand img { width:48px; height:48px; border-radius:6px; }
  .brand h1 { margin:0; font-size:18px; color:#0f172a; }

  /* Order summary */
  .order-meta { margin-top:18px; display:flex; justify-content:space-between; flex-wrap:wrap; gap:10px; }
  .meta-item { font-size:13px; color:#64748b; }

  /* Items table */
  .items { width:100%; margin-top:18px; border-top:1px solid #eef2f7; }
  .item-row { width:100%; padding:14px 0; border-bottom:1px solid #f1f5f9; display:flex; align-items:center; gap:12px; }
  .item-thumb { width:64px; height:64px; background:#f8fafc; border-radius:6px; overflow:hidden; flex:0 0 64px; }
  .item-thumb img { width:100%; height:100%; object-fit:cover; display:block; }
  .item-desc { flex:1; font-size:14px; color:#0f172a; }
  .item-qty { width:60px; text-align:right; color:#475569; }
  .item-price { width:110px; text-align:right; font-weight:600; color:#0f172a; }

  /* Totals */
  .totals { margin-top:16px; width:100%; }
  .totals-row { display:flex; justify-content:space-between; padding:8px 0; color:#334155; font-size:14px; }
  .totals-row.total { font-size:16px; font-weight:700; color:#0f172a; border-top:1px dashed #e2e8f0; padding-top:12px; margin-top:6px; }

  /* Addresses */
  .addresses { display:flex; gap:18px; margin-top:20px; flex-wrap:wrap; }
  .address-box { flex:1; min-width:180px; background:#f8fafc; padding:12px; border-radius:6px; font-size:13px; color:#475569; }

  /* CTA */
  .btn { display:inline-block; background:#0f172a; color:#ffffff; padding:10px 16px; border-radius:8px; font-weight:600; text-decoration:none; margin-top:18px; }

  /* Footer */
  .email-footer { padding:18px; text-align:center; font-size:12px; color:#94a3b8; }

  /* Mobile */
  @media only screen and (max-width:520px) {
    .email-inner { padding:18px; }
    .item-price, .item-qty { width:70px; font-size:13px; }
    .brand h1 { font-size:16px; }
  }
</style>
</head>
<body>
  <!-- Preheader: hidden preview text -->
  <span style="display:none; font-size:1px; color:#fff; max-height:0; max-width:0; opacity:0; overflow:hidden;">
    Terimakasih atas pesanan anda! Berikut adalah rincian transaksi (Order #{{ $order->code }}).
  </span>

  <table class="email-wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
    <tr>
      <td align="center">
        <table class="email-body" width="100%" cellpadding="0" cellspacing="0" role="presentation">
          <tr>
            <td class="email-inner">
              <!-- Header -->
              <table width="100%" role="presentation">
                <tr>
                  <td>
                    <div class="brand" aria-label="brand">
                      <img src="#" alt="logo" />
                      <h1>TokoKu</h1>
                    </div>
                  </td>
                  <td align="right" style="vertical-align:middle;">
                    <div style="font-size:13px; color:#64748b;">Order receipt</div>
                  </td>
                </tr>
              </table>

              <!-- Greeting -->
              <p style="margin:18px 0 6px 0;">Hi {{ $order->recipient_name }},</p>
              <p style="margin:0 0 12px 0; color:#475569;">
                Terimakasih! Kami akan segera memproses pesanan anda. Berikut rincian transaksinya
              </p>

              <!-- Order meta -->
              <div class="order-meta" role="group" aria-label="order summary">
                <div class="meta-item"><strong>Order #</strong> {{ $order->code }}</div>
                <div class="meta-item"><strong>Tanggal</strong> {{ $order->created_at->format('d F Y H:i:s') }}</div>
                <div class="meta-item"><strong>Status</strong> {{ $order->order_status }}</div>
                <div class="meta-item"><strong>Pembayaran</strong> {{ $order->payment_method }}</div>
              </div>

              <!-- Items -->
              <div class="items" role="table" aria-label="items purchased">
                <!-- Repeat this block for each item -->
                @foreach($order->orderDetails as $orderItem)
                  <div class="item-row" role="row">
                    <div class="item-thumb" role="gridcell">
                      <img src="{{ $orderItem->product->image->url }}" alt="{{ $orderItem->product->name }}">
                    </div>
                    <div class="item-desc" role="gridcell">
                      <div style="font-weight:600; color:#0f172a;">{{ $orderItem->name_snapshot }}</div>
                      <div style="font-size:13px; color:#64748b;">{{ '[variantName]' }}</div>
                    </div>
                    <div class="item-qty" role="gridcell">x{{ $orderItem->quantity }}</div>
                    <div class="item-price" role="gridcell">{{ App\Helpers\Utils::currencyFormat($orderItem->price) }}</div>
                  </div>
                @endforeach
                <!-- End item block -->
              </div>

              <!-- Totals -->
              <div class="totals" role="group" aria-label="summary totals">
                <div class="totals-row"><div>Subtotal</div><div>{{ App\Helpers\Utils::currencyFormat($order->total_price) }}</div></div>
                <div class="totals-row"><div>Ongkir</div><div>{{ App\Helpers\Utils::currencyFormat($order->shipping_price) }}</div></div>
                <div class="totals-row"><div>Pemrosesan</div><div>{{ App\Helpers\Utils::currencyFormat($order->payment_fee) }}</div></div>
                <div class="totals-row total"><div>Total</div><div>{{ App\Helpers\Utils::currencyFormat($order->grand_total) }}</div></div>
              </div>

              <!-- Billing & Shipping -->
              <div class="addresses" role="group" aria-label="addresses">
                <div class="address-box">
                  <strong>Dikirim Ke</strong>
                  <div style="margin-top:8px;">{{ $order->recipient_name }}</div>
                  <div>{{ $order->full_address }}</div>
                  <div style="margin-top:8px; font-size:13px; color:#64748b;">Ekspedisi: {{ $order->courier }}</div>
                  <div style="margin-top:8px; font-size:13px; color:#64748b;">Nomor telp: {{'shipping_phone'}}</div>
                </div>
              </div>

              <!-- CTA -->
              <a class="btn" href="{{ route('orders.details', ['orderCode' => $order->code]) }}" target="_blank" rel="noopener noreferrer">Lihat pesananmu</a>

              <!-- Support -->
              <p style="margin:18px 0 0 0; color:#64748b; font-size:13px;">
                Jika memiliki pertanyaan, silahkan hubungi email berikut
                <a href="#" style="color:#0f172a; font-weight:600;">{{'support_email'}}</a>.
              </p>

            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="background:#f8fafc; padding:18px;">
              <div style="font-family: system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial; font-size:13px; color:#64748b; text-align:center;">
                <div style="margin-bottom:6px;">Anda menerima email ini karena telah melakukan pembelian pada {{'TokoKu'}}.</div>
                <div style="font-size:12px;">© 2025 {{'TokoKu'}}. All rights reserved.</div>

              </div>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
