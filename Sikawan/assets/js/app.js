/* assets/js/app.js - JavaScript sederhana untuk seluruh halaman */

document.addEventListener('DOMContentLoaded', function () {

    // ---- Tipe pesanan: tampilkan meja hanya untuk makan di tempat ----
    var pilihanTipe = document.querySelectorAll('[data-tipe]');
    var bagianMeja = document.getElementById('pilih-meja');
    var pilihanMeja = document.getElementById('meja');
    if (pilihanTipe.length && bagianMeja && pilihanMeja) {
        var perbaruiPilihanMeja = function () {
            var tipeTerpilih = document.querySelector('[data-tipe]:checked');
            var bungkus = tipeTerpilih && tipeTerpilih.value === 'Bungkus';
            bagianMeja.hidden = bungkus;
            pilihanMeja.disabled = bungkus;
        };

        pilihanTipe.forEach(function (pilihan) {
            pilihan.addEventListener('change', perbaruiPilihanMeja);
        });
        perbaruiPilihanMeja();
    }

    // ---- Cetak laporan dan struk ----
    document.querySelectorAll('[data-cetak]').forEach(function (tombol) {
        tombol.addEventListener('click', function () {
            window.print();
        });
    });

    // QRIS yang dikonfirmasi akan langsung membuka dialog cetak struk.
    if (document.body.classList.contains('struk-auto-print')) {
        var urlCetak = new URL(window.location.href);
        urlCetak.searchParams.delete('print');
        window.history.replaceState({}, '', urlCetak.pathname + urlCetak.search + urlCetak.hash);
        window.setTimeout(function () {
            window.print();
        }, 900);
    }

    // ---- Pembayaran tunai: nominal cepat dan hitung kembalian ----
    var formBayar = document.getElementById('form-bayar');
    var inputUang = document.getElementById('uang');
    var outputKembalian = document.getElementById('kembalian');
    if (formBayar && inputUang && outputKembalian) {
        var totalTagihan = Number(formBayar.dataset.total);
        var formatRupiah = function (nilai) {
            return 'Rp' + Math.max(0, Math.round(nilai)).toLocaleString('id-ID');
        };
        var perbaruiKembalian = function () {
            var nilai = inputUang.value === '' ? NaN : Number(inputUang.value);
            outputKembalian.classList.remove('payment-short');
            if (!Number.isFinite(nilai)) {
                outputKembalian.textContent = '-';
            } else if (nilai < totalTagihan) {
                outputKembalian.textContent = 'Kurang ' + formatRupiah(totalTagihan - nilai);
                outputKembalian.classList.add('payment-short');
            } else {
                outputKembalian.textContent = formatRupiah(nilai - totalTagihan);
            }
        };

        document.querySelectorAll('[data-uang]').forEach(function (tombol) {
            tombol.addEventListener('click', function () {
                inputUang.value = tombol.dataset.uang;
                document.querySelectorAll('[data-uang]').forEach(function (pilihan) {
                    pilihan.classList.toggle('on', pilihan === tombol);
                    pilihan.setAttribute('aria-pressed', pilihan === tombol ? 'true' : 'false');
                });
                perbaruiKembalian();
            });
        });

        inputUang.addEventListener('input', function () {
            document.querySelectorAll('[data-uang]').forEach(function (tombol) {
                var terpilih = tombol.dataset.uang === inputUang.value;
                tombol.classList.toggle('on', terpilih);
                tombol.setAttribute('aria-pressed', terpilih ? 'true' : 'false');
            });
            perbaruiKembalian();
        });
    }

    // ---- Halaman login: tampilkan / sembunyikan password ----
    var tombolMata = document.getElementById('toggle-password');
    var inputPass  = document.getElementById('password');
    if (tombolMata && inputPass) {
        tombolMata.addEventListener('click', function () {
            var tampil = inputPass.type === 'password';
            inputPass.type = tampil ? 'text' : 'password';
            tombolMata.setAttribute('aria-pressed', tampil ? 'true' : 'false');
            tombolMata.setAttribute('aria-label', tampil ? 'Sembunyikan password' : 'Tampilkan password');
            tombolMata.classList.toggle('is-tampil', tampil);
        });
    }

    // ---- Halaman login: "Lupa password?" / "Hubungi pengelola" ----
    var catatan = document.getElementById('catatan-bantuan');
    document.querySelectorAll('.link-bantuan').forEach(function (a) {
        a.addEventListener('click', function (ev) {
            ev.preventDefault();
            if (catatan) { catatan.hidden = false; }
        });
    });
});
