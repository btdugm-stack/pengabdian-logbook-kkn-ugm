#!/usr/bin/env bash
# Smoke load test lokal memakai Apache Bench (ab), bawaan Laragon.
# BUKAN uji beban skala produksi - itu perlu server staging terpisah.
# Pakai: tests/load/presensi-flow.sh [base_url] [requests] [concurrency]
set -euo pipefail

AB="${AB_BIN:-/c/laragon/bin/apache/httpd-2.4.62-240904-win64-VS17/bin/ab.exe}"
BASE_URL="${1:-http://127.0.0.1:8010}"
N="${2:-100}"
C="${3:-10}"

echo "== Load test smoke: $BASE_URL (n=$N, c=$C) =="

for path in "/" "/login" "/search/mahasiswa" "/search/logbook" "/map/public"; do
  echo ""
  echo "--- $path ---"
  "$AB" -n "$N" -c "$C" -q "$BASE_URL$path" 2>&1 | grep -E "Requests per second|Time per request|Failed requests|Non-2xx responses|Complete requests"
done
