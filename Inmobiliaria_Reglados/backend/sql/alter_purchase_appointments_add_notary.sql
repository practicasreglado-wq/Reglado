-- Añade columnas de notaría a purchase_appointments.
-- Los compradores, al agendar cita, indican dónde se va a firmar.

ALTER TABLE purchase_appointments
  ADD COLUMN notary_name    VARCHAR(255) NULL AFTER appointment_date,
  ADD COLUMN notary_address VARCHAR(500) NULL AFTER notary_name,
  ADD COLUMN notary_city    VARCHAR(150) NULL AFTER notary_address,
  ADD COLUMN notary_phone   VARCHAR(50)  NULL AFTER notary_city;
