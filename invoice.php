<?php
session_start();
date_default_timezone_set("Asia/Jakarta"); // jam & nomor invoice mengikuti WIB

// Harga ditentukan server, bukan dari form
$menu = [
    "nasi_goreng" => ["nama" => "Nasi Goreng", "harga" => 15000],
    "mie_ayam"    => ["nama" => "Mie Ayam",    "harga" => 12000],
];

function rupiah($n) { return "Rp" . number_format($n, 0, ",", "."); }

// ---------- GET ----------
if ($_SERVER["REQUEST_METHOD"] === "GET") {
    // Data JSON pesanan terakhir
    if (isset($_GET["data"])) {
        header("Content-Type: application/json; charset=utf-8");
        header("Cache-Control: no-store");
        if (isset($_SESSION["invoice"])) {
            echo json_encode($_SESSION["invoice"]);
        } else {
            http_response_code(404);
            echo json_encode(["error" => "Belum ada pesanan."]);
        }
        exit;
    }
    // Tampilan invoice versi PHP (tab baru)
    if (isset($_GET["lihat"])) {
        $d = $_SESSION["invoice"] ?? null;
        ?>
<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Invoice</title><link rel="stylesheet" href="style.css"></head>
<body>
<div class="kartu">
  <h1>Invoice</h1>
<?php if ($d === null): ?>
  <div class="error">Belum ada pesanan.</div>
  <a class="tombol" href="index.html">Buat Pesanan</a>
<?php else: ?>
  <div class="inv-head"><span class="badge">&#10004; Pesanan Diproses</span></div>
  <div class="inv-meta">
    <div><small>No. Invoice</small><b><?= htmlspecialchars($d["nomor"]) ?></b></div>
    <div><small>Tanggal</small><b><?= htmlspecialchars($d["tanggal"]) ?></b></div>
    <div><small>Nama</small><b><?= htmlspecialchars($d["nama"]) ?></b></div>
  </div>
  <table>
    <thead><tr><th>No</th><th>Menu</th><th class="r">Harga</th><th class="r">Jml</th><th class="r">Subtotal</th></tr></thead>
    <tbody>
    <?php foreach ($d["items"] as $i => $it): ?>
      <tr>
        <td><?= $i + 1 ?></td>
        <td><?= htmlspecialchars($it["menu"]) ?></td>
        <td class="r"><?= rupiah($it["harga"]) ?></td>
        <td class="r"><?= (int)$it["jumlah"] ?></td>
        <td class="r"><?= rupiah($it["subtotal"]) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot><tr><td colspan="4">Total Bayar</td><td class="r"><?= rupiah($d["total"]) ?></td></tr></tfoot>
  </table>
  <div class="aksi">
    <a class="tombol" href="index.html">Pesan Lagi</a>
    <button type="button" class="tombol sekunder" onclick="window.print()">Cetak</button>
  </div>
<?php endif; ?>
</div>
</body></html>
<?php
        exit;
    }
    header("Location: index.html");
    exit;
}

// ---------- POST ----------
$errors = [];
$items  = [];
$nama   = trim($_POST["nama"] ?? "");
$menuIn = $_POST["menu"]   ?? [];
$jmlIn  = $_POST["jumlah"] ?? [];
$token  = preg_replace("/[^a-z0-9]/i", "", (string)($_POST["token"] ?? ""));

// Validasi ulang di server (validasi JS bisa dilewati)
if ($nama === "") {
    $errors[] = "Nama wajib diisi.";
} elseif (strlen($nama) < 3) {
    $errors[] = "Nama minimal 3 karakter.";
} elseif (!preg_match("/^[A-Za-z\s.']+$/", $nama)) {
    $errors[] = "Nama hanya boleh berisi huruf.";
}

if (!is_array($menuIn) || !is_array($jmlIn)) {
    $errors[] = "Format data pesanan tidak valid.";
} else {
    foreach ($menuIn as $i => $kode) {
        $kode = is_string($kode) ? $kode : "";
        $jml  = isset($jmlIn[$i]) && is_string($jmlIn[$i]) ? trim($jmlIn[$i]) : "";
        if ($kode === "" && $jml === "") continue;

        $no = (int)$i + 1;
        if (!array_key_exists($kode, $menu)) {
            $errors[] = "Menu $no: menu tidak valid.";
            continue;
        }
        $angka = filter_var($jml, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1, "max_range" => 99]]);
        if ($angka === false) {
            $errors[] = "Menu $no: jumlah harus bilangan bulat 1-99.";
            continue;
        }
        $items[$kode] = ($items[$kode] ?? 0) + $angka;
    }
    if (empty($items) && empty($errors)) {
        $errors[] = "Pilih minimal satu menu.";
    }
}

// Data valid: simpan pesanan, lalu tampilkan invoice versi PHP
if (empty($errors)) {
    $baris = [];
    $total = 0;
    foreach ($items as $kode => $jml) {
        $subtotal = $menu[$kode]["harga"] * $jml;
        $total += $subtotal;
        $baris[] = [
            "menu"     => $menu[$kode]["nama"],
            "harga"    => $menu[$kode]["harga"],
            "jumlah"   => $jml,
            "subtotal" => $subtotal,
        ];
    }
    $_SESSION["invoice"] = [
        "token"   => $token,
        "nomor"   => "INV-" . date("YmdHis"),
        "tanggal" => date("d-m-Y H:i") . " WIB",
        "nama"    => $nama,
        "items"   => $baris,
        "total"   => $total,
    ];
    header("Location: invoice.php?lihat=1");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Pesanan Gagal</title><link rel="stylesheet" href="style.css"></head>
<body>
<div class="kartu">
  <h1>Pesanan Gagal</h1>
  <div class="error"><ul>
    <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
  </ul></div>
  <a class="tombol" href="index.html">Kembali</a>
</div>
</body></html>
