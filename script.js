const MENU = { nasi_goreng: "Nasi Goreng", mie_ayam: "Mie Ayam" };
const MAKS_BARIS = Object.keys(MENU).length;
const daftar = document.getElementById("daftarBaris");

function jumlahValid(teks) {
  const n = Number(teks);
  return teks !== "" && Number.isInteger(n) && n >= 1 && n <= 99;
}

function buatBaris() {
  const div = document.createElement("div");
  div.className = "baris";
  let opsi = '<option value="">Pilih Menu</option>';
  for (const kode in MENU) opsi += `<option value="${kode}">${MENU[kode]}</option>`;
  div.innerHTML = `
    <label>Menu:</label>
    <select name="menu[]">${opsi}</select>
    <label>Jumlah:</label>
    <input type="number" name="jumlah[]" min="1" max="99">
    <div class="konfirmasi">
      <button type="button" class="hapus">Hapus pesanan</button>
      <button type="button" class="tambah">Tambah menu lain</button>
    </div>`;
  div.querySelector("select").addEventListener("change", perbaruiOpsi);
  div.querySelector("input").addEventListener("input", perbaruiOpsi);
  div.querySelector(".hapus").addEventListener("click", () => hapusBaris(div));
  div.querySelector(".tambah").addEventListener("click", () => {
    buatBaris().querySelector("select").focus();
  });
  daftar.appendChild(div);
  perbaruiOpsi();
  return div;
}

function hapusBaris(div) {
  div.remove();
  if (daftar.querySelectorAll(".baris").length === 0) buatBaris();
  perbaruiOpsi();
}

// Menu yang sudah dipilih dinonaktifkan di baris lain; atur blok konfirmasi
// Dropdown baru hanya muncul setelah "Tambah menu lain" diklik
function perbaruiOpsi() {
  const baris = [...daftar.querySelectorAll(".baris")];
  const terpilih = baris.map(b => b.querySelector("select").value).filter(v => v);
  baris.forEach((b, i) => {
    const s = b.querySelector("select");
    [...s.options].forEach(o => {
      o.disabled = o.value !== "" && o.value !== s.value && terpilih.includes(o.value);
    });
    const jml = b.querySelector("input").value.trim();
    const kosong = s.value === "" && jml === "";
    const valid = s.value !== "" && jumlahValid(jml);
    const terakhir = i === baris.length - 1;
    b.querySelector(".konfirmasi").style.display = kosong ? "none" : "grid";
    // tombol Tambah selalu tampil; aktif hanya di baris terakhir yang sudah valid
    const tambah = b.querySelector(".tambah");
    tambah.disabled = !(valid && terakhir && baris.length < MAKS_BARIS);
    tambah.title = baris.length >= MAKS_BARIS && terakhir ? "Semua menu sudah dipilih" : "";
  });
}

// Validasi sisi klien
function validasi(e) {
  const errors = [];
  const nama = document.getElementById("nama").value.trim();

  if (nama === "") errors.push("Nama wajib diisi.");
  else if (nama.length < 3) errors.push("Nama minimal 3 karakter.");
  else if (!/^[A-Za-z\s.']+$/.test(nama)) errors.push("Nama hanya boleh berisi huruf.");

  let jumlahItem = 0;
  daftar.querySelectorAll(".baris").forEach((baris, i) => {
    const menu = baris.querySelector("select").value;
    const jml = baris.querySelector("input").value.trim();
    if (menu === "" && jml === "") return;
    if (menu === "") { errors.push(`Menu ${i + 1}: pilih menu terlebih dahulu.`); return; }
    if (!jumlahValid(jml)) errors.push(`Menu ${i + 1}: jumlah harus bilangan bulat 1-99.`);
    else jumlahItem++;
  });
  if (jumlahItem === 0 && errors.length === 0) errors.push("Pilih minimal satu menu.");

  const kotak = document.getElementById("pesanError");
  if (errors.length > 0) {
    e.preventDefault();
    kotak.innerHTML = "<ul>" + errors.map(m => `<li>${m}</li>`).join("") + "</ul>";
    kotak.style.display = "block";
  } else {
    kotak.style.display = "none";
    document.getElementById("token").value = Date.now().toString(36);
  }
}

const formPesan = document.getElementById("formPesan");
formPesan.addEventListener("submit", validasi);
buatBaris();
