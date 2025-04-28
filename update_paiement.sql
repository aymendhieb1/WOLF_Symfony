-- Drop the numero_card column as we don't need it with Stripe
ALTER TABLE paiement DROP COLUMN IF EXISTS numero_card;

-- Modify montant column to be decimal
ALTER TABLE paiement MODIFY COLUMN montant DECIMAL(10,2);
 
-- Add date_paiement column if it doesn't exist
ALTER TABLE paiement ADD COLUMN IF NOT EXISTS date_paiement DATETIME; 