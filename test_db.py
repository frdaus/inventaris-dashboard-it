import os
import pymysql
from dotenv import load_dotenv

load_dotenv()

conn = pymysql.connect(
    host=os.getenv("DB_HOST"),
    port=int(os.getenv("DB_PORT", "3306")),
    user=os.getenv("DB_USER"),
    password=os.getenv("DB_PASSWORD"),
    database=os.getenv("DB_NAME"),
    cursorclass=pymysql.cursors.DictCursor
)

try:
    with conn.cursor() as cursor:
        cursor.execute("SELECT COUNT(*) AS total FROM aset")
        result = cursor.fetchone()
        print("Koneksi MySQL berhasil!")
        print("Total jumlah baris aset:", result["total"])
finally:
    conn.close()
