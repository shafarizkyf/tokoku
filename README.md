# TokoKu

TokoKu adalah aplikasi web berbasis self-hosted yang membantu pelaku usaha memiliki toko online sendiri tanpa bergantung pada platform e-commerce besar di Indonesia.

Dengan TokoKu, pelaku usaha:
- Memiliki kontrol penuh atas infrastruktur dan data, karena aplikasi dijalankan di server milik sendiri.
- Dapat mengurangi biaya tambahan yang biasanya muncul saat berjualan melalui platform e-commerce.


### Integrasi Fitur
- **Gerbang Pembayaran**: [TriPay](tripay.co.id)
- **Cek ongkir**: [Komerce](komerce.id) *(dulunya RajaOngkir)*
- **Autentikasi**: Google OAuth; digunakan untuk memasukan barang ke keranjang, checkout, dan melihat status order.
- **Search Engine**: [MeiliSearch](meilisearch.com); digunkan untuk mencari product dengan memasukan kata kunci.
- **Caching**: [Redis](redis.io)