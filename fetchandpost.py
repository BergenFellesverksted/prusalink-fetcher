# deploy this script on a local machine on the same network as your printers
# in our case, for instance the doorlocks Raspberry Pi
# it will fetch the status of each printer and post it to an online endpoint

#!/usr/bin/env python3
import requests
from requests.auth import HTTPDigestAuth
import sys
from constants import printers  # import the printer definitions

# 1. Define your printers
# (done in constants.py)

# 2. Endpoint to receive status updates
post_url = "https://yoururl.com/post3dprinter_status.php"

# 3. Loop: fetch & forward
for p in printers:
    status_url = f"http://{p['ip']}/api/v1/status"
    auth      = HTTPDigestAuth(p["username"], p["password"])

    try:
        # fetch from printer
        r = requests.get(status_url, auth=auth, timeout=10)
        r.raise_for_status()
        data = r.json()

        # prepare payload for PHP endpoint
        payload = {
            "printer": {
                "name": p["name"],
                "ip":   p["ip"]
            },
            "status": data
        }

        # send to your PHP script
        resp = requests.post(post_url, json=payload, timeout=10)
        resp.raise_for_status()
        print(f"[OK]   {p['name']} → {resp.text.strip()}")

    except requests.exceptions.RequestException as e:
        print(f"[ERROR] {p['name']} ({p['ip']}): {e}", file=sys.stderr)
