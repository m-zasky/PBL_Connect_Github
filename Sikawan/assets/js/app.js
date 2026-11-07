/**
 * app.js
 * JavaScript kecil untuk interaksi di sisi browser.
 * Logika utama (login, keranjang, pembayaran, database) tetap dikerjakan PHP.
 */
document.addEventListener('DOMContentLoaded', () => {

    // Format angka ke rupiah, contoh 25000 -> Rp25.000
    const rupiah = n => 'Rp' + Number(n).toLocaleString('id-ID');

    /* 1. Konfirmasi sebelum aksi berbahaya.
          Tambahkan data-konfirmasi="pesan" pada <form> atau <a>. */
    document.querySelectorAll('[data-konfirmasi]').forEach(el => {
        const peristiwa = el.tagName === 'FORM' ? 'submit' : 'click';
        el.addEventListener(peristiwa, e => {
            if (!confirm(el.dataset.konfirmasi)) e.preventDefault();
        });
    });

    /* 2. Tombol cetak: <button data-cetak> membuka dialog print browser. */
    document.querySelectorAll('[data-cetak]').forEach(btn => {
        btn.addEventListener('click', () => window.print());
    });

    /* 3. Pesan flash sukses hilang sendiri setelah 2,5 detik. */
    document.querySelectorAll('.alert-ok[data-flash]').forEach(el => {
        setTimeout(() => el.remove(), 2500);
    });

    /* 4. Tampil/sembunyikan password di halaman login. */
    document.querySelectorAll('[data-toggle-password]').forEach(btn => {
        btn.addEventListener('click', () => {
            const input = document.querySelector(btn.dataset.togglePassword);
            input.type = input.type === 'password' ? 'text' : 'password';
        });
    });

    /* 5. Pratinjau foto saat memilih file di form menu. */
    const inputFoto = document.querySelector('[data-preview-foto]');
    if (inputFoto) {
        inputFoto.addEventListener('change', () => {
            const file = inputFoto.files[0];
            const kotak = inputFoto.closest('.baris').querySelector('.th');
            if (!file || !file.type.startsWith('image/') || !kotak) return;
            const reader = new FileReader();
            reader.onload = () => {
                kotak.style.backgroundImage = `url(${reader.result})`;
                kotak.style.backgroundSize = 'cover';
                kotak.textContent = '';            // hapus emoji bila ada
            };
            reader.readAsDataURL(file);
        });
    }

    /* 6. Kasir: sembunyikan pilihan meja jika tipe pesanan "Bungkus". */
    const pilihMeja = document.getElementById('pilih-meja');
    document.querySelectorAll('[data-tipe]').forEach(radio => {
        radio.addEventListener('change', () => {
            if (pilihMeja) pilihMeja.hidden = radio.value === 'Bungkus' && radio.checked;
        });
    });

    /* 7. Halaman pembayaran: hitung kembalian & aktifkan tombol bayar. */
    const formBayar = document.getElementById('form-bayar');
    if (formBayar) {
        const total   = Number(formBayar.dataset.total);
        const metode  = formBayar.dataset.metode;
        const tombol  = document.getElementById('btn-bayar');

        if (metode === 'Tunai') {
            const uang      = document.getElementById('uang');
            const kembalian = document.getElementById('kembalian');

            const hitung = () => {
                const diterima = Number(uang.value) || 0;
                if (diterima >= total) {
                    kembalian.textContent = rupiah(diterima - total);
                    kembalian.className = 'grn b';
                } else {
                    kembalian.textContent = 'Kurang ' + rupiah(total - diterima);
                    kembalian.className = 'red b';
                }
                tombol.disabled = diterima < total;
            };

            uang.addEventListener('input', hitung);
            // Tombol nominal cepat mengisi kolom uang otomatis
            formBayar.querySelectorAll('[data-uang]').forEach(b => {
                b.addEventListener('click', () => { uang.value = b.dataset.uang; hitung(); });
            });
            hitung();

        } else if (metode === 'QRIS') {
            // Hitung mundur masa berlaku kode QRIS (simulasi)
            const timer = document.getElementById('qris-timer');
            const ulang = document.getElementById('qris-ulang');
            let sisa = Number(timer.dataset.detik);

            const tampil = () => {
                const m = String(Math.floor(sisa / 60)).padStart(2, '0');
                const d = String(sisa % 60).padStart(2, '0');
                timer.textContent = `${m}:${d}`;
            };
            tampil();

            const jam = setInterval(() => {
                sisa--;
                if (sisa <= 0) {
                    clearInterval(jam);
                    timer.textContent = 'kedaluwarsa';
                    tombol.disabled = true;      // kode kedaluwarsa tidak bisa dikonfirmasi
                    ulang.hidden = false;        // tampilkan link untuk membuat ulang
                    return;
                }
                tampil();
            }, 1000);
        }
    }
});
