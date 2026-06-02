# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**CatFin-R** — a catering finance management system. Vanilla PHP REST API backend + single-page HTML/JS frontend. No build step, no framework, no package manager.

## Running the Project

Requires XAMPP/WAMP (Apache + PHP + MySQL). Place the project under `htdocs/` and access via `http://localhost/FinalSE/frontend/`.

**Database setup:**
1. Import `database/catfinr.sql` in phpMyAdmin (drops and recreates the `catfinr` database).
2. If the database already exists from an older import, also run `database/add_stok_history.sql` to add the `stok_history` table.

**No build, lint, or test commands** — this is plain PHP/HTML/CSS/JS.

## Architecture

### Backend — PHP REST API

`backend/api/<resource>/index.php` handles GET / POST / PUT / DELETE for each resource via `$_SERVER['REQUEST_METHOD']`. All responses are JSON. PDO connection is in `backend/config/db.php` (host `localhost`, db `catfinr`, user `root`, no password).

| Endpoint file | Resource |
|---|---|
| `produk/index.php` | Product CRUD |
| `produk/stok.php` | Add stock via recipe (deducts bahan baku) |
| `produk/update_stok.php` | Manual add/remove stock + logs to `stok_history` |
| `bahanbaku/index.php` | Raw material CRUD |
| `bahanbaku/update_stok.php` | Add raw material stock + logs to `stok_history` |
| `resep/index.php` | Recipe (product ↔ bahan baku mapping) CRUD |
| `dashboard/index.php` | Aggregated stok stats + stok alerts only (not financial totals) |
| `history/index.php` | Read `stok_history` log (`?type=produk` or `?type=bahanbaku`) |
| `pemasukan/index.php` | Income CRUD |
| `pengeluaran/index.php` | Expense CRUD |

**stok_history dependency:** `update_stok.php` and `bahanbaku/update_stok.php` INSERT into `stok_history` inside a transaction. If that table is missing, the whole operation rolls back silently.

### Frontend — Single-Page App

`frontend/index.html` is one large file (~1780 lines) containing all CSS, HTML sections, and JavaScript inline. Sections are shown/hidden via `.sec.active` class — `goTo(page, el)` switches the active section.

**Key JS patterns:**
- `const API = '../backend/api'` — all fetch calls use relative paths with forward slashes.
- `renderProduk(rows)` / `renderBahan(rows)` — rebuild `<tbody>` from API data and also repopulate `<select>` dropdowns used by other modals.
- Modal state uses module-level `let` variables (`_epId`, `_ebId`, `_uspId`, etc.) set by `open*()` functions before the modal opens.
- After any save, code calls `await fetchProduk()`/`await fetchBahan()` then `await fetchDashboard()` to refresh all affected UI. `fetchDashboard()` also calls `fetchDashboardHistory()` at the end.
- `setSelect(id, val)` — helper that matches by option value or text to restore a `<select>` to the correct value when opening an edit modal.

### Dashboard Data Flow

The four summary cards on the dashboard get their values from **different sources** — not the dashboard API:

| Card | Source |
|---|---|
| **Total Pendapatan** (`#dashPendapatan`) | `updateLaporan()` — sum of `_pmAll` |
| **Total Pengeluaran** (`#dashPengeluaran`) | `updateLaporan()` — sum of `_pnAll` |
| **Laba Bersih** (`#dashLabaBersih`) | `updateLaporan()` — same formula as Laporan Keuangan page |
| **Pesanan Terjual** (`#dashPesananTerjual`) | `fetchDashboardHistory()` — sums `jumlah` where `action_type = 'remove_sold'` |

`updateLaporan()` is called at the end of both `renderPemasukan()` and `renderPengeluaran()`, so the dashboard cards refresh automatically whenever pemasukan or pengeluaran data is fetched.

**Arus Kas chart** — built dynamically from `_pmAll`/`_pnAll` by `updateDashChart()` (called from `updateLaporan()`). Harian mode = last 7 days; Bulanan mode = last 6 months. Values stored in the chart are in thousands of Rupiah; Y-axis callback appends `'k'` (harian) or divides by 1000 and appends `'jt'` (bulanan).

**Riwayat Produk panel** — replaces the old "Produk Terlaris" section. `fetchDashboardHistory()` fetches `history/index.php?type=produk` and `renderDashRiwayat()` displays the 5 most recent entries from `stok_history`.

`fetchDashboard()` (called after every CRUD operation) handles: stok produk/bahan stats, stok alerts, and notification panel. It does **not** update the financial summary cards — those come from `updateLaporan()`.

### Database Schema

Tables: `produk`, `bahanbaku`, `resep`, `pemasukan`, `pengeluaran`, `stok_history`.

`resep` links `produk.id` ↔ `bahanbaku.id` with cascade deletes. `stok.php` queries resep to auto-deduct ingredients when producing a product; if no recipe exists, stock is incremented directly.

`stok_history` columns: `entity_type` (enum `produk`/`bahanbaku`), `entity_id`, `entity_nama`, `action_type` (`add`, `remove_sold`, `remove_spoiled`, `produksi`, `tambah`), `jumlah`, `stok_before`, `stok_after`, `satuan`, `keterangan`, `created_at`.

## Key Gotchas

- **Satuan options** in `ebUnit` (Edit Bahan modal) and `tbUnitB` (Tambah Bahan modal) must stay in sync — both selects list the allowed units. Adding a new unit requires updating both selects in `index.html`.
- **Produk category options** must be consistent across `tpKatB` (Tambah), `epKat` (Edit), and the `fKatProduk` filter select. A mismatch causes the `setSelect` helper to silently leave the wrong category selected on edit.
- `catfinr.sql` seeds `stok` as 0 for all products (stock starts empty; use the Tambah Stok flow to add).
- The `dashboard/index.php` API is **not** the source of truth for financial totals on the dashboard — those are computed client-side from `_pmAll`/`_pnAll`. Only stok-related stats come from that endpoint.
- **Bahan Hampir Habis count** — `dashboard/index.php` uses `WHERE stok > 0 AND stok <= stok_minimum`. The `_badge()` function uses the same boundary (`stk <= smn` for "Rendah"). Items with `stok === 0` are "Habis" (danger), not "Hampir Habis". Keep both definitions in sync.
- **Edit Bahan modal (`mEB`) has no Stok field** — stok is intentionally read-only in edit. `openEB()` saves current stok to `_ebStk`, and `saveEB()` uses `_ebStk` to preserve the value. To change stok use the Tambah Stok flow (`mTambahBahan` → Bahan Sebelumnya tab).
- **`setSelect` and whitespace** — always call `setSelect` with `.trim()` on textContent values (e.g., `c[1].textContent.trim()`). Hidden trailing/leading spaces in DB data will cause the helper to silently fall through to the first option.
