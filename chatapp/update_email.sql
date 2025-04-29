-- Allow NULL values for email
ALTER TABLE users MODIFY email varchar(255) NULL;

-- Update the specific email to NULL
UPDATE users SET email = NULL WHERE email = 'syed.khadeen@example.com'; 