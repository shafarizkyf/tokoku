# TokoKu

TokoKu adalah aplikasi web berbasis self-hosted yang membantu pelaku usaha memiliki toko online sendiri tanpa bergantung pada platform e-commerce besar di Indonesia.

Dengan TokoKu, pelaku usaha:
- Memiliki kontrol penuh atas infrastruktur dan data, karena aplikasi dijalankan di server milik sendiri.
- Dapat mengurangi biaya tambahan yang biasanya muncul saat berjualan melalui platform e-commerce.


### Integrasi Fitur
- **Gerbang Pembayaran**: [TriPay](tripay.co.id). Digunakan untuk menerima pembayaran melalui Bank/MiniMart/QRIS.
- **Kodepos**: [PosIndonesia](kodepos.posindonesia.co.id/). Digunakan untuk mendapatkan kodepos. Pencarian berdasarkan nama wilayah.
- **Cek Ongkir**: [Komerce](komerce.id) *(dulunya RajaOngkir)*. Digunakan untuk mendapatkan pilihan ekspedisi beserta biayanya.
- **Cek Resi**: [BinderByte](binderbyte.com). Digunakan untuk mendapatkan update pengiriman paket dari ekspedisi
- **Autentikasi**: Google OAuth. Digunakan untuk memasukan barang ke keranjang, checkout, dan melihat status order.
- **Notifikasi**:
  - [Gmail](https://developers.google.com/workspace/gmail/api/guides): Digunakan untuk mengirim salinan kuitansi kepada kostumer saat pesanan telah dibayar
  - [WhatsApp](https://developers.facebook.com/docs/whatsapp): Digunakan untuk mengirim notifikasi kepada owner ketika mendapatkan pesanan baru
- **Web Analytics**: [PostHog](posthog.com). Digunakan untuk mendapatkan data pengunjung website

### Tech Stack
- **Framework**: [Laravel](laravel.com)
- **Caching**: [Redis](redis.io)
- **Search Engine**: [MeiliSearch](meilisearch.com). Digunkan untuk mencari product dengan memasukan kata kunci.

### Ringkasan Bisnis Proses Transaksi Jual Beli
 - **Sebagai Kosumer**
   - Kostumer memasukan barang ke keranjang
   - Kostumer memasukan alamat pengiriman
   - Kosumer memilih metode pembayaran
   - Sistem menampilkan detail order
   - Setelah kostumer melakukan pembayaran, sistem akan mengirimkan salinan detail order melalui email
   - Penjual memproses pesanan anda
   - Kostumer dapat melihat detail order secara berkala untuk melihat status pengiriman
   - Kostumer menerima barang
 - **Sebagai Penjual**
   - Admin mendapatkan notifikasi pesanan masuk
   - Admin memproses pesanan
   - Admin memberikan nomor resi pada order