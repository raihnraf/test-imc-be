CRUD User, Level, Page, dan User Permission
Stack: Angular 20, Slim PHP, PostgreSQL

Tipe tes Full-stack web application
Output utama Source code, database script/migration
Fokus penilaian Kualitas struktur aplikasi, logika hak akses, validasi,  keamanan dasar, dan UI

1. Latar Belakang Soal
Sebuah perusahaan membutuhkan aplikasi internal untuk mengatur akses halaman berdasarkan user dan level
jabatan. Aplikasi ini harus memungkinkan administrator membuat data user, level, daftar page/menu, serta
menentukan page mana saja yang boleh diakses oleh user tertentu atau oleh level tertentu.
Peserta diminta membuat aplikasi full-stack dengan Angular 20 sebagai frontend, Slim PHP sebagai REST API
backend, dan PostgreSQL sebagai database. Aplikasi harus menerapkan konsep CRUD, validasi data, relasi antar
tabel, dan pengecekan permission sebelum user mengakses page tertentu.

2. Stack yang Wajib Digunakan
Frontend Angular 20 Gunakan Angular routing, service untuk API, reactive forms atau
template-driven forms, dan route guard untuk halaman terbatas.

Backend Slim PHP Buat REST API yang terstruktur, gunakan middleware untuk
autentikasi dan pengecekan permission.

Database PostgreSQL Buat skema tabel, relasi, constraint, index sederhana, dan seed data
awal.

API Format JSON Seluruh request dan response API menggunakan JSON dengan
format error yang konsisten.

3. Fitur Wajib
3.1 CRUD User
• Administrator dapat menambah, melihat, mengubah, dan menghapus user.
• Minimal field: nama lengkap, username, email, password, level, status aktif/nonaktif.
• Password wajib disimpan dalam bentuk hash, bukan plain text.
• Username dan email harus unik.
• User yang tidak aktif tidak boleh login.
3.2 CRUD Level
• Administrator dapat menambah, melihat, mengubah, dan menghapus level.
• Contoh level: Super Admin, Manager, Staff, Viewer.
• Minimal field: nama level, deskripsi, status aktif/nonaktif.
• Level yang masih digunakan oleh user tidak boleh dihapus langsung, kecuali peserta membuat mekanisme soft
delete.
3.3 CRUD Page
• Administrator dapat menambah, melihat, mengubah, dan menghapus page/menu aplikasi.
• Minimal field: nama page, route/path, deskripsi, urutan tampil, status aktif/nonaktif.
• Route/path harus unik.
• Page dapat ditampilkan sebagai menu di frontend berdasarkan permission user yang login.
3.4 Manajemen User Permission
• Administrator dapat menentukan page yang boleh diakses oleh level tertentu.
• Administrator dapat menentukan page tambahan atau pengecualian untuk user tertentu.
• Aplikasi harus menyediakan tampilan yang mudah digunakan, misalnya permission matrix dengan checkbox
per page.

• Perubahan permission harus langsung memengaruhi daftar menu dan akses route setelah user login ulang
atau refresh token/session.
3.5 Autentikasi dan Otorisasi
• User dapat login menggunakan username/email dan password.
• Backend mengembalikan token atau session yang digunakan frontend untuk request berikutnya.
• Backend wajib menolak akses API/page jika user tidak memiliki permission.
• Frontend wajib menyembunyikan menu yang tidak boleh diakses dan mencegah navigasi menggunakan route
guard.
4. Deliverables yang Harus Dikumpulkan
• Source code frontend: Project Angular 20.
• Source code backend: Project Slim PHP.
• Database: SQL schema/migration dan seed data PostgreSQL.
Ketiga poin di atas diunggah ke Git masing-masing kandidat dan URL source code tersebut dikirimkan kepada
HRD.
5. Batasan dan Ketentuan Pengerjaan
• Peserta boleh menggunakan UI framework seperti Angular Material, CoreUI, PrimeNG, Bootstrap, atau
Tailwind CSS.
• Peserta boleh menggunakan library JWT atau session.
• Peserta boleh menggunakan migration tool atau raw SQL.