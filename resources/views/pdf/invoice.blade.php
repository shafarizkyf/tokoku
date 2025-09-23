<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <title>Invoice</title>
    <style>
      body {
        font-family: DejaVu Sans, sans-serif;
        color: #333;
        font-size: 12px;
        line-height: 1.4;
      }
      .invoice-box {
        width: 100%;
        margin: auto;
        padding: 20px;
        border: 1px solid #eee;
      }
      table {
        width: 100%;
        line-height: inherit;
        text-align: left;
        border-collapse: collapse;
      }
      table td {
        padding: 5px;
        vertical-align: top;
      }
      table tr td:nth-child(2) {
        text-align: right;
      }
      table tr.top table td {
        padding-bottom: 20px;
      }
      table tr.information table td {
        padding-bottom: 20px;
      }
      table tr.heading td {
        background: #f5f5f5;
        border-bottom: 1px solid #ddd;
        font-weight: bold;
      }
      table tr.item td {
        border-bottom: 1px solid #eee;
      }
      table tr.item.last td {
        border-bottom: none;
      }
      table tr.total td:nth-child(2) {
        border-top: 2px solid #333;
        font-weight: bold;
      }
      .logo {
        max-width: 150px;
      }
    </style>
  </head>
  <body>
    <div class="invoice-box">
      <table>
        <tr class="top">
          <td colspan="2">
            <table>
              <tr>
                <td>
                  <h2>Invoice</h2>
                  Invoice #: {{ $order->code }}<br />
                  Dibuat Tanggal: {{ date('d M Y') }}<br />
                </td>
                <td>
                  <img src="{{ public_path('logo.png') }}" class="logo" alt="Company Logo" />
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <tr class="information">
          <td colspan="2">
            <table>
              <tr>
                <td>
                  <strong>Dari:</strong><br />
                  {{ config('app.name') }}<br />
                  Street Address<br />
                  City, Country
                </td>
                <td>
                  <strong>Kepada:</strong><br />
                  {{ $order->user->name }}<br />
                  {{ $order->user->email }}<br />
                  {{ $order->full_address }}
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <tr class="heading">
          <td>Metoda Pembayaran</td>
          <td>Nomor Referensi</td>
        </tr>

        <tr class="details">
          <td>{{ $order->payment_method }}</td>
          <td>{{ $order->payment_response->data->reference }}</td>
        </tr>

        <tr class="heading">
          <td>Barang</td>
          <td>Harga</td>
        </tr>

        @foreach($order->orderDetails as $orderItem)
        <tr class="item">
          <td>{{ $orderItem->product->name }}</td>
          <td>{{ App\Helpers\Utils::currencyFormat($orderItem->subtotal) }}</td>
        </tr>
        @endforeach

        @if($order->shipping_price)
        <tr class="item">
          <td>Pengiriman</td>
          <td>{{ App\Helpers\Utils::currencyFormat($order->shipping_price) }}</td>
        </tr>
        @endif

        @if($order->payment_fee)
        <tr class="item">
          <td>Biaya Pemrosesan</td>
          <td>{{ App\Helpers\Utils::currencyFormat($order->payment_fee) }}</td>
        </tr>
        @endif

        <tr class="total">
          <td></td>
          <td>Total: {{ App\Helpers\Utils::currencyFormat($order->grand_total) }}</td>
        </tr>
      </table>
    </div>
  </body>
</html>
