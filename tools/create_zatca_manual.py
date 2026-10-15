from docx import Document
from docx.shared import Inches, Pt, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.table import WD_TABLE_ALIGNMENT, WD_CELL_VERTICAL_ALIGNMENT
from docx.enum.section import WD_SECTION
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
from docx.enum.style import WD_STYLE_TYPE
from pathlib import Path

OUT = Path(r"C:\xampp\htdocs\hr\docs\Safety_Shop_ZATCA_User_Manual.docx")
OUT.parent.mkdir(parents=True, exist_ok=True)

doc = Document()
sec = doc.sections[0]
sec.page_height, sec.page_width = Inches(11.7), Inches(8.3)
sec.top_margin, sec.bottom_margin = Inches(.65), Inches(.65)
sec.left_margin, sec.right_margin = Inches(.72), Inches(.72)

styles = doc.styles
styles['Normal'].font.name = 'Aptos'
styles['Normal'].font.size = Pt(10)
styles['Normal'].paragraph_format.space_after = Pt(5)
styles['Normal'].paragraph_format.line_spacing = 1.08
for name, size, color in [('Title', 28, '12385B'), ('Heading 1', 18, '12385B'), ('Heading 2', 13, '0E7C7B'), ('Heading 3', 11, '12385B')]:
    styles[name].font.name = 'Aptos Display'
    styles[name].font.size = Pt(size)
    styles[name].font.color.rgb = RGBColor.from_string(color)
    styles[name].font.bold = True
    styles[name].paragraph_format.keep_with_next = True
if 'Procedure' not in styles:
    st = styles.add_style('Procedure', WD_STYLE_TYPE.PARAGRAPH)
    st.base_style = styles['Normal']
    st.font.size = Pt(10)
    st.paragraph_format.left_indent = Inches(.2)
    st.paragraph_format.space_after = Pt(4)

def shade(cell, fill):
    tcPr = cell._tc.get_or_add_tcPr()
    shd = tcPr.find(qn('w:shd')) or OxmlElement('w:shd')
    shd.set(qn('w:fill'), fill)
    tcPr.append(shd)

def borders(cell, color='B7C4CE', size='5'):
    tcPr = cell._tc.get_or_add_tcPr()
    bd = tcPr.find(qn('w:tcBorders')) or OxmlElement('w:tcBorders')
    for edge in ('top','left','bottom','right','insideH','insideV'):
        el = OxmlElement('w:'+edge); el.set(qn('w:val'),'single'); el.set(qn('w:sz'),size); el.set(qn('w:color'),color); bd.append(el)
    tcPr.append(bd)

def table(headers, rows, widths=None):
    t = doc.add_table(rows=1, cols=len(headers)); t.alignment = WD_TABLE_ALIGNMENT.CENTER
    t.autofit = False
    for i,h in enumerate(headers):
        c=t.rows[0].cells[i]; c.text=h; shade(c,'12385B'); borders(c,'12385B')
        for r in c.paragraphs[0].runs: r.font.bold=True; r.font.color.rgb=RGBColor(255,255,255); r.font.size=Pt(9)
    for ri,row in enumerate(rows):
        cells=t.add_row().cells
        for i,val in enumerate(row):
            cells[i].text=str(val); cells[i].vertical_alignment=WD_CELL_VERTICAL_ALIGNMENT.CENTER; borders(cells[i])
            if ri%2: shade(cells[i],'F3F7F8')
            for p in cells[i].paragraphs: p.paragraph_format.space_after=Pt(2)
            for r in cells[i].paragraphs[0].runs: r.font.size=Pt(8.5)
    if widths:
        for row in t.rows:
            for i,w in enumerate(widths): row.cells[i].width=Inches(w)
    doc.add_paragraph()
    return t

def step(n, title, text):
    p=doc.add_paragraph(style='Procedure'); p.paragraph_format.keep_with_next=True
    r=p.add_run(f'{n}. {title} — '); r.bold=True; r.font.color.rgb=RGBColor.from_string('0E7C7B')
    p.add_run(text)

def bullet(text): doc.add_paragraph(text, style='List Bullet')

def callout(title, text, color='E8F3F3'):
    t=doc.add_table(rows=1,cols=1); t.alignment=WD_TABLE_ALIGNMENT.CENTER
    c=t.cell(0,0); shade(c,color); borders(c,'7EA6A6','8')
    p=c.paragraphs[0]; r=p.add_run(title+'  '); r.bold=True; r.font.color.rgb=RGBColor.from_string('12385B'); p.add_run(text)
    doc.add_paragraph()

def page_break(): doc.add_page_break()

# Cover
p=doc.add_paragraph(); p.alignment=WD_ALIGN_PARAGRAPH.CENTER; p.paragraph_format.space_before=Pt(80)
r=p.add_run('HARBOUR EDGE'); r.bold=True; r.font.size=Pt(15); r.font.color.rgb=RGBColor.from_string('E78A1F')
p=doc.add_paragraph('SAFETY SHOP ERP', style='Title'); p.alignment=WD_ALIGN_PARAGRAPH.CENTER
p=doc.add_paragraph('ZATCA Phase 2 Operations & Handover Manual'); p.alignment=WD_ALIGN_PARAGRAPH.CENTER
p.runs[0].font.size=Pt(18); p.runs[0].font.color.rgb=RGBColor.from_string('0E7C7B')
doc.add_paragraph()
table(['Document control','Value'],[
    ['Audience','Company administrator, finance manager, invoice issuer'],
    ['System URL','/safety-shop'],
    ['ZATCA screen','/safety-shop/zatca'],
    ['Version','1.0 · 15 October 2026'],
    ['Scope','Invoice, credit note, debit note, signing, validation, compliance and live submission'],
], [1.7,4.8])
callout('Handover position','The software, official SDK integration, XML generation, signing workflow, audit register, retry controls, credit notes and debit notes are implemented. The customer must supply the FATOORA production OTP and complete ZATCA’s mandatory six-document compliance sequence before the Production CSID can be issued. This regulatory sequence cannot be bypassed.')
p=doc.add_paragraph('Think Safe. Work Safe. Go Home Safe'); p.alignment=WD_ALIGN_PARAGRAPH.CENTER; p.runs[0].bold=True

page_break()
doc.add_heading('1. Purpose and operating model',1)
doc.add_paragraph('This manual gives the complete operational path from master-data setup and invoice creation to ZATCA acceptance. It also explains corrections, returns, debit adjustments, failure handling and production onboarding.')
table(['Document','When to use','ZATCA type','Stock effect'],[
    ['Tax invoice','Normal sale','388 · standard or simplified','Reduces stock when finalized'],
    ['Credit note','Customer return or reduction of an issued invoice','381','Restores returned stock'],
    ['Debit note','Underbilling or post-invoice price increase','383','No stock movement'],
], [1.2,2.55,1.7,1.2])
doc.add_heading('Roles and separation of duties',2)
bullet('Issuer: creates drafts and operational records within the assigned branch.')
bullet('Manager / approver: reviews and posts commercial documents for the company scope.')
bullet('Company administrator: performs EGS onboarding, installs CSIDs, signs, validates and submits ZATCA documents.')
bullet('Superadmin: manages the platform and companies; it does not operate a customer’s tax submissions.')
callout('Security rule','The OTP belongs to the customer. The authorized customer user enters it directly in the ERP. The OTP is not displayed to the service provider and is not stored.')

doc.add_heading('2. Pre-go-live checklist',1)
table(['Check','Required result'],[
    ['Seller legal identity','English and Arabic legal names completed'],
    ['VAT number','Valid Saudi 15-digit VAT number beginning and ending with 3'],
    ['National address','Building no., street, district, city, postal code, additional no., country'],
    ['Bank details','Account name, bank, account number, IBAN and branch verified'],
    ['Customer master','Business buyers have VAT, CR and complete national address'],
    ['Catalog and stock','SKU, unit, VAT rate, selling price and branch stock verified'],
    ['Server','HTTPS in production; correct time/time zone; backups enabled'],
    ['ZATCA SDK','Installed and available to the application'],
], [2.0,4.5])

page_break()
doc.add_heading('3. One-time ZATCA onboarding',1)
doc.add_paragraph('Open Safety Shop → ZATCA Integration. Use a Company Administrator account. Complete the steps in order.')
step(1,'Select environment','Choose Simulation for rehearsal or Production for go-live. Tick the explicit production authorization only for approved go-live.')
step(2,'Name the EGS unit','Use a stable device name identifying the company and ERP unit, then save the configuration.')
step(3,'Generate Key & CSR','Click Generate key & CSR. The system creates a secp256k1 private key, encrypts it with the application key, and prepares the CSR. Never regenerate after a CSID is installed unless performing controlled re-onboarding.')
step(4,'Customer obtains OTP','The authorized company user signs in to the ZATCA FATOORA portal, registers or onboards the solution unit and generates a six-digit OTP.')
step(5,'Customer enters OTP','In Simulation or Production, the customer types the applicable six-digit OTP directly into the ERP and clicks Request Compliance CSID. In Simulation, the configured server OTP is only a fallback when the field is blank. OTPs expire and are single-use; generate a new one if rejected or expired.')
step(6,'Run six compliance checks','Prepare, sign, officially validate and submit one document for each required combination listed below.')
table(['Buyer flow','Invoice','Credit note','Debit note'],[
    ['Standard / B2B','Required','Required','Required'],
    ['Simplified / B2C','Required','Required','Required'],
], [2.2,1.4,1.4,1.4])
step(7,'Request Production CSID','After all six statuses are Compliance Passed, click Request Production CSID. The ERP locks this action until the six-document evidence exists.')
step(8,'Controlled acceptance','Create a real approved test document, sign and validate it, confirm the destination and totals, then activate the operational submission procedure.')
callout('Why OTP alone is not enough','ZATCA requires a Compliance CSID, successful compliance samples, and a Production CSID. The ERP guides and enforces these steps; the customer only needs to provide the OTP and approve the documents, but the mandatory gateway sequence still runs.')

page_break()
doc.add_heading('4. Create and finalize a tax invoice',1)
step(1,'Confirm customer','Open Customers and select or create the buyer. Do not use a generic walk-in record. For a standard invoice, VAT number, CR and full national address are mandatory.')
step(2,'Open invoice form','Go to Safety Shop → Invoices → New invoice.')
step(3,'Choose source','For an approved quotation, tick Invoice from quotation and select it. Items, prices, customer, discount and optional PO number are populated. Repeated quotation products are combined by quantity.')
step(4,'Enter dates and PO','Enter invoice date. PO No. is optional. Supply date is checked against the invoice rules and cannot be in the future where prohibited by the configured workflow.')
step(5,'Add items','Scan a barcode to auto-add and clear the scan field, search a product, or use manual entry. Confirm branch availability. A quotation cannot become an invoice if stock is insufficient.')
step(6,'Review commercial values','Check quantities, unit price, discount, taxable amount, 15% VAT, total and the user-entered notes. Discounts are allocated consistently for tax and later returns.')
step(7,'Record payment','An invoice may be unpaid, partially paid or fully paid. Use cash, card and/or bank allocations. Payment status is financial status; it does not determine whether the tax invoice may be issued.')
step(8,'Save draft or finalize','Save Draft for editable work with a DRAFT watermark and no stock movement or controlled invoice number. Finalize only after review; finalization creates the immutable invoice and stock movement.')
step(9,'Review PDF','Open the invoice PDF. Verify seller/buyer identity, Arabic and English headings, items, VAT, PO number, QR, watermark/status and bank details. Eight compact item rows fit on one page; longer invoices continue automatically.')
callout('Finalization control','A finalized invoice linked to ZATCA must not be edited. Correct it with a credit note, debit note, or controlled void-and-replacement procedure as applicable.')

page_break()
doc.add_heading('5. Prepare, sign and submit an invoice',1)
step(1,'Open ZATCA register','Go to Safety Shop → ZATCA Integration. Finalized controlled invoices appear under Invoices ready to prepare.')
step(2,'Prepare XML','Click Prepare XML. The system validates seller, buyer, totals and line reconciliation; assigns UUID and ICV; links the previous invoice hash; and stores an immutable UBL snapshot.')
step(3,'Sign and validate','Click Sign & validate. The official ZATCA SDK signs with the encrypted EGS key and validates XSD, EN16931, KSA rules and the PIH chain. Do not continue unless SDK status is PASSED.')
step(4,'Compliance or production','During onboarding, click Compliance check for each required sample. After the Production CSID is installed and Production is selected, final posting signs, validates and submits automatically. Standard documents are issued only after clearance; simplified documents are signed before issue and queued for reporting.')
step(5,'Confirm acceptance','Standard/B2B uses clearance and must show CLEARED. Simplified/B2C uses reporting and must show REPORTED. Download XML from the register when needed.')
table(['Register status','Meaning','Operator action'],[
    ['Prepared','Unsigned immutable XML exists','Sign and validate'],
    ['Compliance passed','ZATCA accepted onboarding sample','Complete remaining combinations'],
    ['Cleared','Standard document accepted','Issue/store cleared result'],
    ['Reported','Simplified document accepted','Retain acceptance evidence'],
    ['Failed','Gateway or validation rejected','Read error, correct master/workflow issue, controlled retry'],
], [1.45,2.5,2.65])
callout('Hash-chain protection','Prepare and sign documents sequentially. The ERP blocks preparing another document while the latest one remains unsigned, protecting ICV and previous-invoice hash continuity.')

doc.add_heading('6. Credit note / customer return',1)
step(1,'Find original invoice','Open Customer Returns and select the finalized invoice.')
step(2,'Select returned lines','Enter only the returnable quantities and scan/verify the product barcode when requested. The ERP blocks quantities above the unreturned balance.')
step(3,'Post return','Choose the refund method and enter a clear reason. Posting creates a tax credit note, proportionally reverses discount and VAT, and restores inventory.')
step(4,'ZATCA processing','In Production Ready state, posting automatically prepares, signs, validates and submits the credit note. During Simulation onboarding, use the ZATCA register to prepare and run the compliance check manually.')

page_break()
doc.add_heading('7. Debit note / underbilling adjustment',1)
step(1,'Open Debit Notes','Go to Safety Shop → Debit Notes → New debit note.')
step(2,'Reference invoice','Search and select the original finalized invoice. A debit note is always traceable to that invoice.')
step(3,'Enter adjustment lines','Describe the underbilling or price adjustment, choose unit, quantity and unit price. VAT and totals calculate automatically.')
step(4,'State the reason','Enter a specific commercial reason. Debit notes increase the amount due but do not move stock.')
step(5,'Post and verify','Post the note and review its professional PDF. Posted notes are immutable audit documents.')
step(6,'ZATCA processing','In Production Ready state, posting automatically prepares, signs, validates and submits the debit note. During Simulation onboarding, use the ZATCA register to prepare and run the compliance check manually.')
callout('Verified document rules','The implemented invoice (388), credit note (381) and debit note (383) XML structures pass the installed official SDK stages: XSD, EN16931, KSA and PIH.')

doc.add_heading('8. Payments and invoice status',1)
bullet('Unpaid: no successful payments recorded; invoice remains valid and submit-ready.')
bullet('Partially paid: payments are below invoice total; the PARTIAL status/watermark is shown as configured.')
bullet('Paid: successful payments equal invoice total; no paid watermark is required on the PDF.')
bullet('Payment reversals require a reason and are audited. They recalculate payment status without changing the issued tax values.')
bullet('Never create a credit note merely to record a payment. Credit notes adjust the taxable supply; payments settle the receivable.')

page_break()
doc.add_heading('9. Daily operating SOP',1)
table(['When','Company administrator / finance action'],[
    ['Start of day','Confirm ZATCA device is Production Ready, server clock is correct and no failed documents remain unresolved.'],
    ['Before finalizing','Verify customer type/data, branch stock, item tax, discount, PO number and totals.'],
    ['After finalizing','Prepare XML, sign and obtain SDK PASSED.'],
    ['Submission','Submit. Confirm CLEARED for standard or REPORTED for simplified.'],
    ['Corrections','Use return/credit note for reductions; debit note for underbilling. Reference original invoice.'],
    ['End of day','Review document register, failed status, sequence continuity and export audit CSV if required.'],
    ['Backup','Confirm encrypted application/database backups and restricted access to APP_KEY and EGS credentials.'],
], [1.3,5.2])
doc.add_heading('Failure and retry procedure',2)
step(1,'Do not recreate blindly','A failed record and payload remain in the audit register. Avoid duplicate documents.')
step(2,'Read the error','Identify whether failure is local SDK validation, credential/environment, connectivity, buyer/seller master data, totals, or ZATCA rejection.')
step(3,'Correct the source safely','Correct master data before finalization where possible. For an already issued taxable document, use the appropriate correction document; never edit accepted XML.')
step(4,'Retry under control','After fixing the cause, the scheduled monitor retries due failures automatically; an administrator may also use the controlled submit action. The record tracks request, response, attempts, last error and next retry time.')
step(5,'Renew before expiry','When the dashboard warns that a Production CSID is approaching expiry, generate the renewal CSR, have the customer enter a fresh renewal OTP, and complete renewal. The active credential remains available until the replacement succeeds.')
step(5,'Escalate credentials','For expired/revoked certificates, stop submission and perform controlled EGS re-onboarding. Never replace production keys informally.')

doc.add_heading('10. Troubleshooting quick reference',1)
table(['Message / symptom','Resolution'],[
    ['Invalid or expired OTP','Generate a new six-digit OTP in FATOORA and enter it immediately.'],
    ['Buyer VAT invalid','Use a 15-digit Saudi VAT number starting and ending with 3, or select simplified/B2C when legally appropriate.'],
    ['National address incomplete','Complete building, street, district, city, postal and additional numbers.'],
    ['SDK validation failed','Open register error details; correct the specified tax, identity or reconciliation issue.'],
    ['Production submission unavailable','Install Production CSID, select Production, sign XML and obtain SDK PASSED.'],
    ['Firewall / connection error','Allow the documented ZATCA endpoints over HTTPS; verify DNS, TLS and server time with IT.'],
    ['Previous document unsigned','Sign/validate the latest ICV before preparing the next document.'],
], [2.0,4.5])

page_break()
doc.add_heading('11. Handover acceptance checklist',1)
table(['Acceptance item','Owner','Sign-off'],[
    ['Company legal, VAT, CR and address data verified','Customer','☐'],
    ['Customers and product tax masters verified','Customer','☐'],
    ['Simulation invoice/credit/debit documents validated','ERP provider + Customer','☐'],
    ['Backup/restore and server security verified','IT','☐'],
    ['Production OTP entered directly by customer','Customer','☐'],
    ['Compliance CSID installed','Company admin','☐'],
    ['Six required compliance checks passed','Company admin','☐'],
    ['Production CSID installed','Company admin','☐'],
    ['Standard clearance accepted','Customer + ERP provider','☐'],
    ['Simplified reporting accepted','Customer + ERP provider','☐'],
    ['Audit CSV and failure workflow reviewed','Customer','☐'],
], [3.5,2.2,.7])
callout('Production decision','Do not treat a locally valid XML or successful simulation as production acceptance. Go live only after the customer’s Production CSID is installed and ZATCA returns the expected production status for controlled acceptance documents.')
doc.add_heading('Support information to capture',2)
bullet('Company code and EGS unit name (never send the OTP or private key).')
bullet('Document UUID, ICV, invoice/note number and timestamp.')
bullet('Environment, submission mode, SDK status and ZATCA status.')
bullet('Exact error shown in the audit register and any gateway response identifier.')

# Header/footer
for section in doc.sections:
    hp=section.header.paragraphs[0]; hp.text='HARBOUR EDGE  |  Safety Shop ZATCA Operations'; hp.alignment=WD_ALIGN_PARAGRAPH.RIGHT
    for r in hp.runs: r.font.size=Pt(8); r.font.color.rgb=RGBColor.from_string('607D8B')
    fp=section.footer.paragraphs[0]; fp.alignment=WD_ALIGN_PARAGRAPH.CENTER
    fp.add_run('Controlled user manual  •  Page ')
    fld=OxmlElement('w:fldSimple'); fld.set(qn('w:instr'),'PAGE'); fp._p.append(fld)
    for r in fp.runs: r.font.size=Pt(8); r.font.color.rgb=RGBColor.from_string('607D8B')

doc.core_properties.title='Safety Shop ZATCA User Manual'
doc.core_properties.subject='Invoice creation through ZATCA submission, credit notes, debit notes and handover'
doc.core_properties.author='Harbour Edge'
doc.save(OUT)
print(OUT)
