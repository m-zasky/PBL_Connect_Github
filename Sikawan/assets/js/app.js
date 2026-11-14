/* assets/js/app.js - JavaScript sederhana untuk seluruh halaman */

// Lock zoom in/out while keeping the browser's reset-to-100% shortcut available.
document.addEventListener('keydown', function (event) {
    if ((event.ctrlKey || event.metaKey) && ['+', '-', '=', '_'].indexOf(event.key) !== -1) {
        event.preventDefault();
    }
}, true);

document.addEventListener('wheel', function (event) {
    if (event.ctrlKey) {
        event.preventDefault();
    }
}, { passive: false });

document.addEventListener('DOMContentLoaded', function () {

    // ---- Pencarian menu langsung tanpa memuat ulang halaman ----
    var formCariMenu = document.getElementById('cari-menu');
    var inputCariMenu = document.getElementById('q');
    var kembaliPesanan = document.querySelector('#form-pesanan input[name="kembali"]');
    var gridMenu = document.querySelector('.lstw .grid');
    var navigasiKategori = document.querySelector('.lstw .chips');
    var navigasiHalaman = document.querySelector('.lstw .pg');
    var statusMenu = document.getElementById('status-menu');

    if (formCariMenu && inputCariMenu && gridMenu && navigasiKategori && navigasiHalaman && statusMenu) {
        var waktuTungguCari;
        var nomorPermintaan = 0;
        var kategoriAktif = new URL(window.location.href).searchParams.get('kategori') || 'Semua';
        var formatHarga = function (harga) {
            return 'Rp' + Number(harga).toLocaleString('id-ID');
        };

        var buatUrl = function (opsi) {
            var url = new URL(formCariMenu.action, window.location.href);
            url.search = '';
            if (opsi.kategori && opsi.kategori !== 'Semua') {
                url.searchParams.set('kategori', opsi.kategori);
            }
            if (opsi.q) {
                url.searchParams.set('q', opsi.q);
            }
            if (opsi.halaman > 1) {
                url.searchParams.set('hal', String(opsi.halaman));
            }
            var ringkasan = new URL(window.location.href).searchParams.get('ringkasan');
            if (ringkasan) {
                url.searchParams.set('ringkasan', ringkasan);
            }
            return url;
        };

        var perbaruiTautanFilter = function () {
            navigasiKategori.querySelectorAll('[data-kategori]').forEach(function (tautan) {
                var kategori = tautan.dataset.kategori;
                tautan.href = buatUrl({ kategori: kategori, q: inputCariMenu.value.trim(), halaman: 1 }).href;
                tautan.classList.toggle('on', kategori === kategoriAktif);
                if (kategori === kategoriAktif) {
                    tautan.setAttribute('aria-current', 'page');
                } else {
                    tautan.removeAttribute('aria-current');
                }
            });
        };

        var perbaruiUrlKembali = function (halaman) {
            if (kembaliPesanan) {
                var url = buatUrl({
                    kategori: kategoriAktif,
                    q: inputCariMenu.value.trim(),
                    halaman: halaman
                });
                kembaliPesanan.value = url.pathname + url.search;
            }
        };

        var buatKartuMenu = function (menu) {
            var kartu = document.createElement('article');
            kartu.className = 'card mi lst' + (menu.jumlah_dipilih ? ' sel' : '') + (menu.tersedia ? '' : ' off');

            var templateGambar = document.createElement('template');
            templateGambar.innerHTML = menu.gambar;
            kartu.appendChild(templateGambar.content.firstElementChild);

            if (menu.jumlah_dipilih) {
                var tandaDipilih = document.createElement('span');
                tandaDipilih.className = 'tag';
                tandaDipilih.style.background = 'var(--or)';
                tandaDipilih.style.color = '#fff';
                tandaDipilih.textContent = menu.jumlah_dipilih + ' dipilih';
                kartu.appendChild(tandaDipilih);
            }

            var isi = document.createElement('div');
            isi.className = 'bd';
            var kategori = document.createElement('div');
            kategori.className = 'sm mut';
            kategori.textContent = menu.kategori + (menu.tersedia ? '' : ' • Habis');
            var nama = document.createElement('b');
            nama.textContent = menu.nama;
            var baris = document.createElement('div');
            baris.className = 'baris';
            var harga = document.createElement('b');
            harga.className = 'red';
            harga.textContent = formatHarga(menu.harga);
            baris.appendChild(harga);

            if (menu.tersedia) {
                var tombolTambah = document.createElement('button');
                tombolTambah.type = 'submit';
                tombolTambah.className = 'plus';
                tombolTambah.setAttribute('form', 'form-pesanan');
                tombolTambah.name = 'aksi';
                tombolTambah.value = 'tambah:' + menu.id;
                tombolTambah.setAttribute('aria-label', 'Tambah ' + menu.nama);
                tombolTambah.textContent = '+';
                baris.appendChild(tombolTambah);
            }

            isi.appendChild(kategori);
            isi.appendChild(nama);
            isi.appendChild(baris);
            kartu.appendChild(isi);
            return kartu;
        };

        var tampilkanHalaman = function (data, opsi) {
            gridMenu.replaceChildren();
            if (data.menu.length) {
                data.menu.forEach(function (menu) {
                    gridMenu.appendChild(buatKartuMenu(menu));
                });
            } else {
                var kosong = document.createElement('p');
                kosong.className = 'mut';
                kosong.textContent = 'Menu tidak ditemukan.';
                gridMenu.appendChild(kosong);
            }

            var jumlahTampil = navigasiHalaman.querySelector('span');
            jumlahTampil.textContent = 'Menampilkan ' + data.menu.length + ' dari ' + data.total + ' menu';
            navigasiHalaman.querySelectorAll('a').forEach(function (tautan) {
                tautan.remove();
            });
            for (var halaman = 1; halaman <= data.total_halaman; halaman++) {
                var tautanHalaman = document.createElement('a');
                tautanHalaman.href = buatUrl({ kategori: opsi.kategori, q: opsi.q, halaman: halaman }).href;
                tautanHalaman.dataset.halaman = String(halaman);
                tautanHalaman.textContent = String(halaman);
                if (halaman === data.halaman) {
                    tautanHalaman.className = 'on';
                    tautanHalaman.setAttribute('aria-current', 'page');
                }
                navigasiHalaman.appendChild(tautanHalaman);
            }
        };

        var cariMenu = async function (halaman, metodeRiwayat) {
            var opsi = {
                kategori: kategoriAktif,
                q: inputCariMenu.value.trim(),
                halaman: halaman
            };
            var url = buatUrl(opsi);
            url.searchParams.set('ajax', 'menu');
            var permintaanSaatIni = ++nomorPermintaan;

            try {
                var respons = await fetch(url.href, {
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin'
                });
                if (!respons.ok) {
                    throw new Error('Pencarian menu gagal dengan status ' + respons.status + '.');
                }
                var data = await respons.json();
                if (permintaanSaatIni !== nomorPermintaan) {
                    return;
                }

                tampilkanHalaman(data, opsi);
                perbaruiTautanFilter();
                perbaruiUrlKembali(data.halaman);
                if (metodeRiwayat === 'push') {
                    window.history.pushState({}, '', buatUrl({
                        kategori: opsi.kategori,
                        q: opsi.q,
                        halaman: data.halaman
                    }));
                } else if (metodeRiwayat === 'replace') {
                    window.history.replaceState({}, '', buatUrl({
                        kategori: opsi.kategori,
                        q: opsi.q,
                        halaman: data.halaman
                    }));
                }
                statusMenu.hidden = true;
            } catch (error) {
                if (permintaanSaatIni !== nomorPermintaan) {
                    return;
                }
                console.error(error);
                statusMenu.textContent = 'Pencarian gagal diperbarui. Periksa koneksi lalu coba lagi.';
                statusMenu.hidden = false;
            }
        };

        formCariMenu.addEventListener('submit', function (event) {
            event.preventDefault();
            window.clearTimeout(waktuTungguCari);
            cariMenu(1, 'replace');
        });

        inputCariMenu.addEventListener('input', function () {
            window.clearTimeout(waktuTungguCari);
            perbaruiUrlKembali(1);
            waktuTungguCari = window.setTimeout(function () {
                cariMenu(1, 'replace');
            }, 250);
        });

        navigasiKategori.addEventListener('click', function (event) {
            var tautan = event.target.closest('[data-kategori]');
            if (!tautan || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) {
                return;
            }
            event.preventDefault();
            window.clearTimeout(waktuTungguCari);
            kategoriAktif = tautan.dataset.kategori;
            perbaruiUrlKembali(1);
            cariMenu(1, 'push');
        });

        navigasiHalaman.addEventListener('click', function (event) {
            var tautan = event.target.closest('[data-halaman]');
            if (!tautan || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) {
                return;
            }
            event.preventDefault();
            perbaruiUrlKembali(Number(tautan.dataset.halaman));
            cariMenu(Number(tautan.dataset.halaman), 'push');
        });

        window.addEventListener('popstate', function () {
            var parameter = new URL(window.location.href).searchParams;
            inputCariMenu.value = parameter.get('q') || '';
            kategoriAktif = parameter.get('kategori') || 'Semua';
            perbaruiUrlKembali(Number(parameter.get('hal')) || 1);
            cariMenu(Number(parameter.get('hal')) || 1, 'none');
        });

        perbaruiTautanFilter();
    }

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
