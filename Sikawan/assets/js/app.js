/* assets/js/app.js - JavaScript sederhana untuk seluruh halaman */

document.addEventListener('DOMContentLoaded', function () {

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
