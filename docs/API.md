# CHITS 2.0 — REST API Contract

Base URL: `https://your-rhu.local/api/v1`
Auth: Bearer token (Laravel Sanctum)
Format: JSON

---

## Patients

```
GET    /patients                    List patients (filterable: barangay_id, program, q)
POST   /patients                    Register new patient
GET    /patients/{id}               Get patient record
PUT    /patients/{id}               Update patient
GET    /patients/{id}/consultations Patient consultation timeline
GET    /patients/{id}/vitals        Vital signs history
GET    /patients/{id}/lab           Lab results
GET    /patients/{id}/appointments  Appointments
```

## Consultations
```
GET    /consultations               List (filterable: date, provider, program)
POST   /consultations               Save new consult (SOAP + vitals + ICD-10)
GET    /consultations/{id}          Get single consult
```

## Family Planning
```
GET    /fp                          All FP acceptors
POST   /fp                          Record FP service
GET    /fp/summary                  CPR, method mix, dropouts
```

## Maternal Care
```
GET    /maternal                    Active pregnancies
POST   /maternal                    Register pregnancy
POST   /maternal/{id}/visit         Record prenatal visit
```

## TB / NTP
```
GET    /ntp                         TB register
POST   /ntp                         Enroll patient
PUT    /ntp/{id}                    Update treatment status
GET    /ntp/summary                 Success rate, defaulters, cohort
```

## Notifiable Diseases
```
GET    /notifiable                  All reports (filterable: disease, month, barangay)
POST   /notifiable                  Report a case
GET    /notifiable/summary          Counts vs threshold by disease
GET    /notifiable/alerts           Threshold-exceeded diseases
```

## Lab
```
GET    /lab                         Lab requests/results
POST   /lab                         New request
PUT    /lab/{id}                    Enter result
```

## Drugs
```
GET    /drugs                       Inventory list
PUT    /drugs/{id}/restock          Add stock
POST   /drugs/{id}/dispense         Reduce stock (tied to consult)
```

## Appointments
```
GET    /appointments                List (filterable: date, provider, status)
POST   /appointments                Schedule
PUT    /appointments/{id}           Update status
POST   /appointments/{id}/sms       Send SMS reminder (Semaphore)
```

## Reports (return PDF or JSON)
```
GET    /reports/daily-service       Daily service report
GET    /reports/morbidity?month=    FHSIS M1 morbidity
GET    /reports/epi-weekly?week=    Epi weekly monitoring
GET    /reports/fp-monthly?month=   FP monthly
GET    /reports/fp-tcl              FP target client list
GET    /reports/prenatal            Prenatal TCL
GET    /reports/tb-register         TB register
GET    /reports/tb-summary?quarter= TB cohort summary
GET    /reports/demographic         Demographic profile
```

## Admin
```
GET    /users                       System users
POST   /users                       Create user
PUT    /users/{id}                  Update
GET    /barangays                   Barangay list
GET    /health-center               Config
PUT    /health-center               Update config
POST   /auth/login                  { login, password } → { token }
POST   /auth/logout
```
