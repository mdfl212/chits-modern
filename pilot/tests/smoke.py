#!/usr/bin/env python3
"""End-to-end smoke test against the local development pilot using fictional data."""
import http.cookiejar
import json
import os
import sys
import urllib.error
import urllib.request

BASE = "http://127.0.0.1:8080"
cookies = http.cookiejar.CookieJar()
opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cookies))

def request(path, method="GET", body=None, headers=None, expected=200):
    h = {"Accept": "application/json"}
    if body is not None:
        h["Content-Type"] = "application/json"
    h.update(headers or {})
    data = None if body is None else json.dumps(body).encode()
    req = urllib.request.Request(BASE + path, data=data, headers=h, method=method)
    try:
        with opener.open(req, timeout=5) as response:
            status = response.status
            raw = response.read()
    except urllib.error.HTTPError as e:
        status, raw = e.code, e.read()
    if status != expected:
        raise AssertionError(f"{method} {path}: expected HTTP {expected}, got {status}: {raw[:500]!r}")
    if not raw:
        return {}
    return json.loads(raw.decode())

def main():
    # Protected routes must not be accessible before authentication.
    request("/api/patients", expected=401)
    csrf = request("/api/csrf")["csrfToken"]
    login = request("/api/login", "POST", {"username":"ci-user","password":"ci-test-password-12345"},
                    {"X-CSRF-Token":csrf}, 200)
    csrf = login["csrfToken"]
    assert login["user"]["username"] == "ci-user"

    household = request("/api/households", "POST", {"householdHead":"Synthetic Household Head","barangay":"Synthetic Barangay","householdCode":"HH-CI-001","address":"Synthetic Sitio"}, {"X-CSRF-Token":csrf}, 201)["household"]
    households = request("/api/households")["households"]
    assert any(h["id"] == household["id"] and h["member_count"] == 0 for h in households)

    patient = request("/api/patients", "POST", {
        "firstName":"Test", "lastName":"Patient", "dateOfBirth":"1990-01-02",
        "sex":"unknown", "barangay":"Synthetic Barangay", "phone":"", "householdId":household["id"]
    }, {"X-CSRF-Token":csrf}, 201)["patient"]
    pid = patient["id"]
    assert patient["first_name"] == "Test"

    detail = request("/api/patients/" + pid)
    assert detail["patient"]["id"] == pid and detail["consultations"] == []
    family = request("/api/households/" + household["id"])
    assert family["household"]["household_code"] == "HH-CI-001" and len(family["members"]) == 1 and family["members"][0]["id"] == pid

    request("/api/patients/" + pid + "/consultations", "POST", {
        "visitDate":"2026-01-02", "chiefComplaint":"Synthetic cough",
        "subjective":"Fictional symptom history", "objective":"Fictional exam",
        "assessment":"Test assessment", "plan":"Test plan",
        "temperatureC":37.0, "bpSystolic":120, "bpDiastolic":80,
        "pulse":72, "respiratoryRate":16, "spo2":98
    }, {"X-CSRF-Token":csrf}, 201)

    detail = request("/api/patients/" + pid)
    assert len(detail["consultations"]) == 1
    assert detail["consultations"][0]["chief_complaint"] == "Synthetic cough"

    # Validation failure must not create another consultation.
    request("/api/patients/" + pid + "/consultations", "POST", {
        "visitDate":"2026-01-02", "chiefComplaint":"Invalid test",
        "spo2":150
    }, {"X-CSRF-Token":csrf}, 422)
    detail = request("/api/patients/" + pid)
    assert len(detail["consultations"]) == 1

    request("/api/logout", "POST", {}, {"X-CSRF-Token":csrf}, 200)
    request("/api/patients", expected=401)
    print("PASS: auth, CSRF, household creation and membership, patient registration, persistence, consultation history, validation, logout")

if __name__ == "__main__":
    main()
