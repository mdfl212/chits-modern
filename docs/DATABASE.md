# CHITS 2.0 — Database Schema Reference

Sourced from `sql/setup.sql`, `sql/chitszero.sql`, and class inspection.

## Core tables

### `patient`
| Column          | Type         | Notes                              |
|-----------------|--------------|------------------------------------|
| patient_id      | INT PK AI    |                                    |
| lname           | VARCHAR(50)  | Last name                          |
| fname           | VARCHAR(50)  | First name                         |
| mname           | VARCHAR(50)  | Middle name                        |
| dob             | DATE         |                                    |
| sex             | CHAR(1)      | M / F                              |
| mothers_name    | VARCHAR(100) | Mother's maiden name               |
| barangay_id     | INT FK       | → barangay                        |
| address         | TEXT         |                                    |
| philhealth_id   | VARCHAR(20)  |                                    |
| family_id       | INT FK       | → family_folder                   |
| date_registered | DATE         |                                    |

### `family_folder`
| Column          | Type         | Notes                              |
|-----------------|--------------|------------------------------------|
| family_id       | INT PK AI    |                                    |
| head_of_family  | VARCHAR(100) |                                    |
| barangay_id     | INT FK       |                                    |
| address         | TEXT         |                                    |

### `notes` (consultations)
| Column          | Type         | Notes                              |
|-----------------|--------------|------------------------------------|
| notes_id        | INT PK AI    |                                    |
| patient_id      | INT FK       |                                    |
| consult_date    | DATE         |                                    |
| visit_type      | VARCHAR(20)  | new / follow-up / emergency        |
| location        | VARCHAR(30)  | Consultations / Admissions etc.    |
| complaint       | TEXT         | Chief complaint                    |
| hx              | TEXT         | History                            |
| pe              | TEXT         | Physical exam                      |
| diagnosis       | TEXT         |                                    |
| icd10_code      | VARCHAR(10)  |                                    |
| plan            | TEXT         |                                    |
| bp              | VARCHAR(10)  |                                    |
| temp            | DECIMAL(4,1) |                                    |
| hr              | INT          |                                    |
| rr              | INT          |                                    |
| weight          | DECIMAL(5,1) |                                    |
| spo2            | INT          |                                    |
| program_tag     | VARCHAR(20)  |                                    |
| provider_id     | INT FK       | → game_user                       |

### `notifiable`
| Column          | Type         | Notes                              |
|-----------------|--------------|------------------------------------|
| notifiable_id   | INT PK AI    |                                    |
| patient_id      | INT FK       |                                    |
| disease_id      | INT FK       | → m_lib_disease                   |
| onset_date      | DATE         |                                    |
| barangay_id     | INT FK       |                                    |
| report_date     | DATE         |                                    |
| icd10           | VARCHAR(10)  |                                    |
| notes           | TEXT         |                                    |

### `m_lib_disease` (seed data — 33 notifiable diseases)
Populated from original `chitszero.sql` INSERT statements.
Includes: Dengue, Diarrheas, Pneumonias, Influenza, Measles, TB Respiratory,
Hypertension, Malaria, Cholera, Typhoid, Hepatitis A/B, Rabies, Diphtheria,
Neonatal Tetanus, Tetanus, Polio, Pertussis, Gonorrhea, Syphilis, AIDS/HIV,
Leprosy, Filariasis, Meningitis, Leptospirosis, Food Poisoning, Meningococcemia…

### `mc` (maternal care)
gravida, para, aog_weeks, edd, bp, fundal_height, fht, weight, provider_id, visit_date

### `fp` (family planning)
method_id, start_date, source, quantity, next_supply_date, provider_id

### `m_lib_fp_method`
Pills (COC), Pills (POP), IUD, Injectables (DMPA), Natural FP (BBT/LAM), Condom, BTL, NSV

### `ntp` (TB register)
tb_number, category, regimen, phase, month_of_treatment, sputum_result, status

### `vaccine_child` (EPI)
bcg, hepb0, dpt1, dpt2, dpt3, opv1, opv2, opv3, measles, given_dates…

### `appointment`
patient_id, appt_date, appt_type, provider_id, sms_sent, sms_number, status

### `lab`
patient_id, exam_id, request_date, result, flag, requestor_id

### `drug` (inventory)
drug_name, formulation, category_id, preparation, qty_on_hand, reorder_level

### `game_user` (system users)
login, password (bcrypt in v2), pin, fname, lname, role, priv_add, priv_update,
priv_delete, priv_admin, cellular, email, is_active

### `barangay`
barangay_id, name, area_code, population

### `healthcenter` (config.xml → DB)
name, address, telephone, phic_code, province, region, node_level, parent_code
