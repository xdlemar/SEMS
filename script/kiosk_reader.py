
import os
import time
import tempfile
import requests
import cv2

from smartcard.System import readers
from smartcard.util import toHexString
from smartcard.Exceptions import CardConnectionException

BASE_URL   = "http://localhost:8080"   
API_TAP    = f"{BASE_URL}/sems/api/attendance_tap.php"
API_PHOTO  = f"{BASE_URL}/sems/api/attendance_photo_upload.php"

DEVICE_ID  = "DEV001"                 
CAM_INDEX  = 0                        

DEBOUNCE_SECONDS = 1.0               
POLL_IDLE_SLEEP  = 0.15                
AFTER_TAP_SLEEP  = 0.60                 


HTTP_TIMEOUT = 6                        
RETRY_ON_FAIL = True

def pick_reader():
    """Return a PC/SC reader (prefer ACR122). Waits until one is present."""
    while True:
        try:
            r = readers()
            if r:
                for rd in r:
                    if "ACR122" in str(rd):
                        return rd
                return r[0]
        except Exception:
            pass
        print("No PC/SC reader found. Plug ACR122U… retrying in 1s.")
        time.sleep(1)

def get_uid_once(rd):
    """Try to read card UID once. Returns UID hex (UPPERCASE) or None."""
    try:
        conn = rd.createConnection()
        conn.connect()
      
        GET_UID = [0xFF, 0xCA, 0x00, 0x00, 0x00]
        data, sw1, sw2 = conn.transmit(GET_UID)
        if sw1 == 0x90 and sw2 == 0x00 and data:
            return toHexString(data).replace(" ", "").upper()
        return None
    except CardConnectionException:
       
        return None
    except Exception:
     
        return None

_session = requests.Session()

def post_tap(uid):
    """
    POST to attendance_tap.php.
      success: {'status':'ok','type':'IN'|'OUT','log_id':..., 'employee_id':...}
      reject : {'status':'error','code':'UNREGISTERED', 'message':...}
    Retries once on transient network errors.
    """
    body = {"rfid_uid": uid, "device_id": DEVICE_ID, "ts": time.strftime("%Y-%m-%dT%H:%M:%S")}
    attempts = (1, 2) if RETRY_ON_FAIL else (1,)
    for i in attempts:
        try:
            r = _session.post(API_TAP, json=body, timeout=HTTP_TIMEOUT)
            r.raise_for_status()
            return r.json()
        except requests.RequestException as e:
            if i == attempts[-1]:
                raise
            print("Tap post failed, retrying…", e)
            time.sleep(0.5)

def _open_camera(index):
    """
    Try to open camera with several backends (Windows likes DSHOW).
    Returns an opened cv2.VideoCapture or None.
    """
    backends = [cv2.CAP_DSHOW, cv2.CAP_MSMF, cv2.CAP_ANY]
    for be in backends:
        try:
            cam = cv2.VideoCapture(index, be)
            if cam is not None and cam.isOpened():
                return cam
            if cam is not None:
                cam.release()
        except Exception:
            pass
    return None

def capture_and_upload(uid):
    """Capture one frame and upload JPEG to the server."""
    cam = _open_camera(CAM_INDEX)
    if cam is None:
        print("Camera busy or not available. If a browser tab is using it, close that preview or switch CAM_INDEX.")
        return

    cam.set(cv2.CAP_PROP_FRAME_WIDTH,  640)
    cam.set(cv2.CAP_PROP_FRAME_HEIGHT, 480)

   
    for _ in range(8):
        ok, _ = cam.read()
        if not ok:
            time.sleep(0.05)

    ok, frame = cam.read()
    cam.release()

    if not ok or frame is None:
        print("Could not grab frame. Another app may still be holding the camera.")
        return

   
    tmp = os.path.join(tempfile.gettempdir(), f"sems_{int(time.time())}.jpg")
    cv2.imwrite(tmp, frame)
    try:
        with open(tmp, "rb") as f:
            resp = _session.post(
                API_PHOTO,
                files={"photo": f},
                data={"rfid_uid": uid, "device_id": DEVICE_ID},
                timeout=HTTP_TIMEOUT
            )
        try:
            j = resp.json()
            if j.get("status") == "ok":
                print("Photo uploaded:", j.get("url"))
            else:
                print("Photo upload rejected:", j)
        except Exception:
            print("Photo upload response:", resp.status_code)
    finally:
        try:
            if os.path.exists(tmp):
                os.remove(tmp)
        except Exception:
            pass

def main():
    rd = pick_reader()
    print("Using reader:", rd)
    print("Device ID:", DEVICE_ID)

    last_uid = None
    last_time = 0.0

    while True:
        try:
            uid = get_uid_once(rd)

            if uid:
                now = time.time()

                if uid == last_uid and (now - last_time) < DEBOUNCE_SECONDS:
                    time.sleep(POLL_IDLE_SLEEP)
                    continue

                print("UID:", uid)

               
                try:
                    res = post_tap(uid)
                except requests.HTTPError as http_err:
                    print("HTTP error calling attendance_tap:", http_err)
                    if "404" in str(http_err):
                        print(f"Check URL: {API_TAP} and that attendance_tap.php exists & port is 8080.")
                    last_uid, last_time = uid, now
                    time.sleep(AFTER_TAP_SLEEP)
                    continue
                except Exception as e:
                    print("Tap network error:", e)
                    last_uid, last_time = uid, now
                    time.sleep(AFTER_TAP_SLEEP)
                    continue

                
                if not isinstance(res, dict):
                    print("Unexpected response:", res)
                    last_uid, last_time = uid, now
                    time.sleep(AFTER_TAP_SLEEP)
                    continue

                if res.get("status") != "ok":
                  
                    code = res.get("code")
                    if code == "UNREGISTERED":
                        print("✖ Unregistered card. No attendance recorded. Bind this UID in Admin → Register Card.")
                    else:
                        print("✖ Tap rejected:", res)
                    last_uid, last_time = uid, now
                    time.sleep(AFTER_TAP_SLEEP)
                    continue

               
                tap_type = res.get("type")
                print(f"✓ Tap accepted: {tap_type} (log_id={res.get('log_id')})")

             
                if tap_type == "IN":
                    try:
                        capture_and_upload(uid)
                    except Exception as e:
                        print("Photo upload error:", e)

              
                last_uid, last_time = uid, now
              
                time.sleep(AFTER_TAP_SLEEP)

            else:
              
                time.sleep(POLL_IDLE_SLEEP)

        except CardConnectionException:
          
            time.sleep(0.2)
        except KeyboardInterrupt:
            print("\nExiting…")
            break
        except Exception as e:
            print("Reader loop error:", e)
            time.sleep(0.8)


if __name__ == "__main__":
    main()