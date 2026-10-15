# ZATCA deployment and handover checklist

## Safe simulation testing

Use **Simulation** on the Safety Shop ZATCA page. Simulation calls the ZATCA simulation gateway and does not create legally issued production invoices. The customer should generate or obtain the simulation OTP and enter it directly in the Compliance CSID form. Never reuse a production OTP in screenshots, email, or support messages.

Run before testing:

```text
php artisan migrate --force
php artisan zatca:readiness --company=COMPANY_ID
php artisan schedule:work
php artisan queue:work --tries=8 --backoff=60
```

Simulation flow:

1. Complete seller legal name, Arabic legal name, 15-digit Saudi VAT number, CR, and national-address fields.
2. Select Simulation and save the EGS solution unit.
3. Generate the encrypted secp256k1 private key and CSR.
4. Enter the customer's six-digit simulation OTP and request the Compliance CSID.
5. Prepare, sign, validate, and compliance-check all six document profiles: standard/simplified invoice, credit note, and debit note.
6. Review the API response and downloaded XML for each profile.
7. Request the Production CSID only after all required compliance checks pass.

## Production handover

- Deploy only over HTTPS. Set `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, and an HTTPS `APP_URL`.
- Use a persistent queue driver such as database or Redis; do not use `sync` for production submission retries.
- Run one queue worker continuously and run `php artisan schedule:run` every minute through the operating-system scheduler.
- Protect `.env`, `APP_KEY`, database backups, private documents, and the SDK working directory. Never copy the encrypted key without its matching protected application key and database.
- The authorized customer enters the production OTP in the application. The service provider should not request or retain that OTP.
- Standard invoices are not printable as final PDFs until clearance succeeds. Simplified documents are signed before issue and queued for reporting.
- Monitor the ZATCA dashboard, failed submissions, certificate-expiry warning, and CSV audit export every business day.
- Test restoration from backup and certificate renewal before the certificate expiry window.

## Go-live acceptance evidence

Retain customer sign-off, environment configuration, six successful compliance responses, Production CSID issuance, one accepted standard clearance, one accepted simplified report, credit/debit note results, retry evidence, audit CSV, and backup/restore evidence. Passing the local SDK alone is a validation aid and is not ZATCA approval.
