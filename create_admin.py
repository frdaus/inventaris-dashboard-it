
import getpass
import pymysql
from werkzeug.security import generate_password_hash
from dotenv import load_dotenv
import os

load_dotenv()

username = input("Username admin: ").strip()
password = getpass.getpass("Password admin: ")

if not username or not password:
    print("Username dan password wajib diisi.")
    raise SystemExit(1)

password_hash = generate_password_hash(password)

conn = pymysql.connect(
    host=os.getenv("DB_HOST", "127.0.0.1"),
    port=int(os.getenv("DB_PORT", "3306")),
    user=os.getenv("DB_USER"),
    password=os.getenv("DB_PASSWORD"),
    database=os.getenv("DB_NAME"),
    cursorclass=pymysql.cursors.DictCursor
)

try:
    with conn.cursor() as cursor:
        cursor.execute(
            """
            INSERT INTO admin (username, password_hash)
            VALUES (%s, %s)
            """,
            (username, password_hash)
        )
    conn.commit()
    print("Akun admin berhasil dibuat.")
except pymysql.err.IntegrityError:
    print("Username sudah digunakan. Pilih username lain.")
finally:
    conn.close()
