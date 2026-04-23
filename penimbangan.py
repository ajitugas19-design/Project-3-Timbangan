import serial
import mysql.connector
import time
import logging
import json
import sys
from datetime import datetime
import argparse
from flask import Flask, jsonify  # pip install flask pyserial mysql-connector-python

# Konfigurasi Logging (enhanced)
logging.basicConfig(
    level=logging.INFO, 
    format='%(asctime)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler('scale_log.txt', encoding='utf-8'),
        logging.StreamHandler(sys.stdout)
    ]
)

# DB Config (XAMPP)
DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'penimbangan'
}

SERIAL_CONFIG = {
    'port': 'COM3',  # Ganti sesuai Device Manager (COM1-20?)
    'baudrate': 9600,
    'bytesize': 8,
    'parity': 'N',
    'stopbits': 1,
    'timeout': 1
}


app = Flask(__name__)

def connect_db():
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        logging.info("✅ DB connected")
        return conn
    except Exception as e:
        logging.error(f"❌ DB Error: {e}")
        return None

def connect_serial():
    try:
        ser = serial.Serial(**SERIAL_CONFIG)
        logging.info(f"✅ Serial {SERIAL_CONFIG['port']} connected")
        return ser
    except Exception as e:
        logging.error(f"❌ Serial Error: {e}")
        return None

def log_to_db(conn, raw_data, weight, status='success'):
    """Log raw + parsed to scale_logs table"""
    try:
        cursor = conn.cursor()
        cursor.execute("""
            INSERT INTO scale_logs (raw_data, parsed_weight, status, device_port)
            VALUES (%s, %s, %s, %s)
        """, (raw_data, weight, status, SERIAL_CONFIG['port']))
        conn.commit()
        cursor.close()
        logging.info(f"📊 Logged DB: {weight}kg (raw='{raw_data[:50]}...')")
    except Exception as e:
        logging.error(f"❌ DB Log Error: {e}")

def read_weight(ser):
    """Read + parse weight"""
    try:
        ser.write(b'\r\n')
        time.sleep(0.5)
        raw = ser.readline().decode('ascii', errors='ignore').strip()
        if raw:
            # Parse: extract number (e.g. '123.45kg' -> 123.45)
            import re
            match = re.search(r'(\d+\.?\d*)', raw)
            if match:
                weight = float(match.group(1))
                log_to_db(conn, raw, weight)
                return weight
        log_to_db(conn, raw or 'timeout', None, 'timeout')
    except Exception as e:
        logging.error(f"❌ Read Error: {e}")
        log_to_db(conn, str(e), None, 'error')
    return None

# Global
conn = None
ser = None
prev_weight = None

@app.route('/latest')
def latest_weight():
    if conn:
        cursor = conn.cursor()
        cursor.execute("SELECT parsed_weight, timestamp FROM scale_logs WHERE status='success' ORDER BY id DESC LIMIT 1")
        row = cursor.fetchone()
        cursor.close()
        return jsonify({'weight': row[0] if row else None, 'time': row[1] if row else None})
    return jsonify({'error': 'Not running'})

@app.route('/logs')
def get_logs():
    from flask import request
    limit = int(request.args.get('limit', 20))
    cursor = conn.cursor()
    cursor.execute(f"SELECT * FROM scale_logs ORDER BY id DESC LIMIT {limit}")
    rows = cursor.fetchall()
    cursor.close()
    return jsonify(rows)


def main_loop():
    global conn, ser, prev_weight
    conn = connect_db()
    if not conn: return
    
    ser = connect_serial()
    if not ser:
        conn.close()
        return
    
    logging.info("🚀 RS232 Monitor Started - Ctrl+C to stop")
    while True:
        try:
            weight = read_weight(ser)
            if weight and (prev_weight is None or abs(weight - prev_weight) > 0.1):
                prev_weight = weight
            time.sleep(2)
        except KeyboardInterrupt:
            break
    
    ser.close()
    conn.close()
    logging.info("🛑 Monitor stopped")

if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    parser.add_argument('--flask', action='store_true', help='Run Flask API only')
    args = parser.parse_args()
    
    if args.flask:
        app.run(host='0.0.0.0', port=5000, debug=False)
    else:
        main_loop()
