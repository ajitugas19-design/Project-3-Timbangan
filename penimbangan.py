import serial
import mysql.connector
import time
import logging
from datetime import datetime
import uuid

# Konfigurasi Logging
logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(levelname)s - %(message)s', handlers=[logging.FileHandler('scale_log.txt'), logging.StreamHandler()])

# Konfigurasi Database (XAMPP default)
DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'penimbangan'
}

# Konfigurasi Serial RS232
SERIAL_CONFIG = {
    'port': 'COM3',  # Ganti sesuai port timbangan
    'baudrate': 9600,
    'bytesize': 8,
    'parity': 'N',
    'stopbits': 1,
    'timeout': 1
}

# Sample data FK untuk testing (sesuaikan)
SAMPLE_DATA = {
    'id_kendaraan': 1,
    'id_supplier': 1,
    'id_material': 1,
    'id_customers': 1
}

def connect_db():
    """Koneksi ke MySQL"""
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        logging.info("Koneksi DB berhasil")
        return conn
    except Exception as e:
        logging.error(f"Error koneksi DB: {e}")
        return None

def connect_serial():
    """Koneksi ke RS232"""
    try:
        ser = serial.Serial(**SERIAL_CONFIG)
        logging.info(f"Serial terhubung: {SERIAL_CONFIG['port']}")
        return ser
    except Exception as e:
        logging.error(f"Error serial {SERIAL_CONFIG['port']}: {e}")
        return None

def read_weight(ser):
    """Baca berat dari timbangan. Asumsi response ASCII number + unit (e.g., '123.45kg\\r\\n')"""
    try:
        # Kirim trigger jika diperlukan (kosong untuk auto-send scales)
        ser.write(b'\r\n')
        time.sleep(0.5)
        line = ser.readline().decode('ascii', errors='ignore').strip()
        # Parse berat: ambil number sebelum kg/g
        if line:
            weight_str = ''.join(filter(lambda x: x.isdigit() or x == '.', line.split()[0]))
            if weight_str:
                weight = float(weight_str)
                logging.info(f"Berat dibaca: {weight} kg")
                return weight
    except Exception as e:
        logging.error(f"Error baca berat: {e}")
    return None

def log_weight(conn, weight):
    """Log berat ke DB: Insert waktu_in dan transaksi (bruto=weight, tara/netto temp)"""
    try:
        cursor = conn.cursor()
        
        # Insert waktu_in (bruto)
        cursor.execute("INSERT INTO waktu_in (jam_in, tanggal_in) VALUES (NOW(), CURDATE())")
        id_in = cursor.lastrowid
        
        # Insert waktu_out temp sama
        cursor.execute("INSERT INTO waktu_out (jam_out, tanggal_out) VALUES (NOW(), CURDATE())")
        id_out = cursor.lastrowid
        
        # Insert transaksi
        no_record = str(uuid.uuid4())[:8] + datetime.now().strftime("%Y%m%d")
        cursor.execute("""
            INSERT INTO transaksi (no_record, id_kendaraan, id_supplier, id_material, id_customers, id_in, id_out, bruto, tara, netto)
            VALUES (%s, %s, %s, %s, %s, %s, %s, %s, 0.00, %s)
        """, (no_record, SAMPLE_DATA['id_kendaraan'], SAMPLE_DATA['id_supplier'], 
              SAMPLE_DATA['id_material'], SAMPLE_DATA['id_customers'], id_in, id_out, weight, weight))
        
        conn.commit()
        cursor.close()
        logging.info(f"Logged: bruto={weight}, id_transaksi baru")
        return True
    except Exception as e:
        logging.error(f"Error log DB: {e}")
        return False

def main():
    logging.info("Mulai monitoring timbangan RS232")
    ser = connect_serial()
    if not ser:
        return
    
    conn = connect_db()
    if not conn:
        ser.close()
        return
    
    prev_weight = None
    while True:
        try:
            weight = read_weight(ser)
            if weight and (prev_weight is None or abs(weight - prev_weight) > 0.1):  # Log jika berubah >0.1kg
                log_weight(conn, weight)
                prev_weight = weight
            time.sleep(2)  # Poll setiap 2 detik
        except KeyboardInterrupt:
            logging.info("Stop monitoring")
            break
        except Exception as e:
            logging.error(f"Error loop: {e}")
            time.sleep(5)
    
    ser.close()
    conn.close()

if __name__ == "__main__":
    main()

