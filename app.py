import os
from flask import Flask, render_template, request, redirect, url_for, flash, session
import pymysql
from dotenv import load_dotenv
from werkzeug.security import check_password_hash

load_dotenv()


app = Flask(__name__)
app.secret_key = os.getenv("FLASK_SECRET_KEY")

if not app.secret_key:
    raise RuntimeError("FLASK_SECRET_KEY belum diatur di file .env")



def get_db_connection():
    return pymysql.connect(
        host=os.getenv("DB_HOST", "mysql"),
        port=int(os.getenv("DB_PORT", "3306")),
        user=os.getenv("DB_USER"),
        password=os.getenv("DB_PASSWORD"),
        database=os.getenv("DB_NAME", "inventaris_db"),
        cursorclass=pymysql.cursors.DictCursor,
        charset="utf8mb4"
    )

from functools import wraps

def login_required(view):
    @wraps(view)
    def wrapped_view(*args, **kwargs):
        if "admin_id" not in session:
            return redirect(url_for("login"))
        return view(*args, **kwargs)
    return wrapped_view


@app.route("/login", methods=["GET", "POST"])
def login():
    if "admin_id" in session:
        return redirect(url_for("dashboard"))

    if request.method == "POST":
        username = request.form.get("username", "").strip()
        password = request.form.get("password", "")

        conn = get_db_connection()
        try:
            with conn.cursor() as cursor:
                cursor.execute(
                    "SELECT id, username, password_hash "
                    "FROM admin WHERE username = %s",
                    (username,)
                )
                admin = cursor.fetchone()
        finally:
            conn.close()

        if admin and check_password_hash(
            admin["password_hash"], password
        ):
            session.clear()
            session["admin_id"] = admin["id"]
            session["admin_username"] = admin["username"]
            return redirect(url_for("dashboard"))

        return render_template(
            "login.html",
            error="Username atau password salah."
        )

    return render_template("login.html")


@app.route("/logout", methods=["POST"])
def logout():
    session.clear()
    return redirect(url_for("login"))


@app.route("/")
@login_required
def dashboard():
    search = request.args.get("q", "").strip()

    conn = get_db_connection()
    try:
        with conn.cursor() as cursor:
            cursor.execute("""
                SELECT
                    COUNT(*) AS total_jenis,
                    COALESCE(SUM(jumlah), 0) AS total_unit,
                    COALESCE(SUM(CASE WHEN kondisi = 'Baik'
                        THEN jumlah ELSE 0 END), 0) AS unit_baik,
                    COALESCE(SUM(CASE WHEN kondisi = 'Rusak'
                        THEN jumlah ELSE 0 END), 0) AS unit_rusak,
                    COALESCE(SUM(CASE WHEN kondisi = 'Maintenance'
                        THEN jumlah ELSE 0 END), 0) AS unit_maintenance
                FROM aset
            """)
            stats = cursor.fetchone()

            cursor.execute("""
                SELECT kategori, SUM(jumlah) AS total
                FROM aset
                GROUP BY kategori
                ORDER BY total DESC
            """)
            category_data = cursor.fetchall()

            sql = """
                SELECT id, kode_aset, nama_barang, kategori,
                       lokasi, jumlah, kondisi, tanggal_masuk
                FROM aset
            """
            params = []

            if search:
                sql += """
                    WHERE kode_aset LIKE %s
                       OR nama_barang LIKE %s
                       OR kategori LIKE %s
                       OR lokasi LIKE %s
                """
                keyword = f"%{search}%"
                params = [keyword, keyword, keyword, keyword]

            sql += " ORDER BY id DESC"

            cursor.execute(sql, params)
            assets = cursor.fetchall()

    finally:
        conn.close()

    return render_template(
        "index.html",
        stats=stats,
        assets=assets,
        category_data=category_data,
        search=search
    )


@app.route("/tambah", methods=["POST"])
@login_required
def tambah_aset():
    kode_aset = request.form.get("kode_aset", "").strip()
    nama_barang = request.form.get("nama_barang", "").strip()
    kategori = request.form.get("kategori", "").strip()
    lokasi = request.form.get("lokasi", "").strip() or None
    kondisi = request.form.get("kondisi", "Baik")
    tanggal_masuk = request.form.get("tanggal_masuk", "").strip() or None

    try:
        jumlah = int(request.form.get("jumlah", "1"))
        if jumlah < 1:
            raise ValueError
    except ValueError:
        flash("Jumlah harus berupa bilangan bulat minimal 1.", "error")
        return redirect(url_for("dashboard"))

    if not kode_aset or not nama_barang or not kategori:
        flash("Kode aset, nama barang, dan kategori wajib diisi.", "error")
        return redirect(url_for("dashboard"))

    if kondisi not in ("Baik", "Rusak", "Maintenance"):
        flash("Pilihan kondisi tidak valid.", "error")
        return redirect(url_for("dashboard"))

    conn = get_db_connection()
    try:
        with conn.cursor() as cursor:
            cursor.execute("""
                INSERT INTO aset
                    (kode_aset, nama_barang, kategori, lokasi,
                     jumlah, kondisi, tanggal_masuk)
                VALUES (%s, %s, %s, %s, %s, %s, %s)
            """, (
                kode_aset, nama_barang, kategori, lokasi,
                jumlah, kondisi, tanggal_masuk
            ))
        conn.commit()
        flash("Aset berhasil ditambahkan.", "success")

    except pymysql.err.IntegrityError:
        conn.rollback()
        flash("Kode aset sudah digunakan. Gunakan kode yang berbeda.", "error")
    finally:
        conn.close()

    return redirect(url_for("dashboard"))


@app.route("/hapus/<int:asset_id>", methods=["POST"])
@login_required
def hapus_aset(asset_id):
    conn = get_db_connection()
    try:
        with conn.cursor() as cursor:
            cursor.execute("DELETE FROM aset WHERE id = %s", (asset_id,))
        conn.commit()
        flash("Aset berhasil dihapus jika datanya ditemukan.", "success")
    finally:
        conn.close()

    return redirect(url_for("dashboard"))
@app.route("/edit/<int:asset_id>", methods=["GET", "POST"])
@login_required
def edit_aset(asset_id):
    conn = get_db_connection()

    try:
        with conn.cursor() as cursor:
            if request.method == "POST":
                kode_aset = request.form.get("kode_aset", "").strip()
                nama_barang = request.form.get("nama_barang", "").strip()
                kategori = request.form.get("kategori", "").strip()
                lokasi = request.form.get("lokasi", "").strip() or None
                kondisi = request.form.get("kondisi", "")
                tanggal_masuk = request.form.get("tanggal_masuk", "").strip() or None

                try:
                    jumlah = int(request.form.get("jumlah", "1"))
                    if jumlah < 1:
                        raise ValueError
                except ValueError:
                    flash("Jumlah harus minimal 1.", "error")
                    return redirect(url_for("edit_aset", asset_id=asset_id))

                if not kode_aset or not nama_barang or not kategori:
                    flash("Kode aset, nama barang, dan kategori wajib diisi.", "error")
                    return redirect(url_for("edit_aset", asset_id=asset_id))

                if kondisi not in ("Baik", "Rusak", "Maintenance"):
                    flash("Kondisi aset tidak valid.", "error")
                    return redirect(url_for("edit_aset", asset_id=asset_id))

                cursor.execute("""
                    UPDATE aset
                    SET kode_aset=%s,
                        nama_barang=%s,
                        kategori=%s,
                        lokasi=%s,
                        jumlah=%s,
                        kondisi=%s,
                        tanggal_masuk=%s
                    WHERE id=%s
                """, (
                    kode_aset, nama_barang, kategori, lokasi,
                    jumlah, kondisi, tanggal_masuk, asset_id
                ))

                conn.commit()
                flash("Data aset berhasil diperbarui.", "success")
                return redirect(url_for("dashboard"))

            cursor.execute("SELECT * FROM aset WHERE id=%s", (asset_id,))
            asset = cursor.fetchone()

            if asset is None:
                return "Aset tidak ditemukan.", 404

            return render_template("edit.html", asset=asset)

    except pymysql.err.IntegrityError:
        conn.rollback()
        flash("Kode aset sudah digunakan aset lain.", "error")
        return redirect(url_for("edit_aset", asset_id=asset_id))
    finally:
        conn.close()

if __name__ == "__main__":
    app.run(host="127.0.0.1", port=5000, debug=True)
