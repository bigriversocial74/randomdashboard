# Gruber AI Procurement Intelligence System
## MVP Navigation and Page Map

## Global application shell

### Header
- Company switcher: Enterprise, GC, GPS, GTS, GMC, EVP
- Date range selector
- Global search
- Import status
- Notifications
- User profile

### Primary navigation
1. Executive Dashboard
2. Procurement
3. Suppliers
4. Items & Inventory
5. Projects & Work Orders
6. Savings
7. AI Briefing Room
8. Reports
9. Administration

---

## 1. Executive Dashboard

### Purpose
Provide leadership with a single operational and financial view across all five businesses.

### Components
- Total purchasing spend
- Open and past-due PO value
- Inventory value
- Excess and obsolete inventory
- Strategic legacy inventory
- Expedite freight
- Expected and realized savings
- Supplier risk alerts
- Top stockout risks
- Company comparison
- AI-generated weekly summary

### Actions
- Filter by company, category, supplier, buyer and date
- Open underlying transactions
- Assign an exception
- Export PDF/CSV
- Generate AI briefing

---

## 2. Procurement

### 2.1 Purchase Requests
- Request list
- Create request
- Request details
- Line items
- Business purpose
- Project/work-order link
- Approval timeline
- Convert approved request to PO

### 2.2 Approvals
- My approvals
- Approval history
- Threshold exceptions
- Emergency-buy approvals

### 2.3 Purchase Orders
- PO list
- Open commitments
- Past-due POs
- Partial receipts
- PO details
- Supplier acknowledgement
- Delivery updates
- Expedite reason
- Cancel/close workflow

### 2.4 Receiving
- Receive against PO
- Quantity and condition verification
- Serial/lot capture
- Quality hold
- Return-to-supplier initiation

### 2.5 Imports
- Upload file
- Map columns
- Validate records
- Review rejected rows
- Approve import
- Import history

---

## 3. Suppliers

### 3.1 Supplier Directory
- Supplier list
- Duplicate suggestions
- Preferred/approved/conditional/blocked status
- Company relationships
- Contacts
- Notes

### 3.2 Supplier Profile
Tabs:
- Overview
- Spend
- Performance
- Contracts
- Items
- Quality events
- Opportunities
- Activity log

### 3.3 Contracts
- Contract list
- Expiration calendar
- Terms and documents
- Enterprise vs company agreement
- Price/service-level compliance

### 3.4 Supplier Scorecards
- On-time delivery
- Complete delivery
- Quality acceptance
- Invoice accuracy
- Responsiveness
- Contract compliance
- Risk
- Grade and action plan

### 3.5 Supplier Reviews
- Review agenda
- Corrective actions
- Negotiation preparation
- Meeting notes and follow-up

---

## 4. Items & Inventory

### 4.1 Item/SKU Master
- Item list
- Shared vs company-specific items
- Manufacturer and part number
- Unit of measure
- Category
- Standard and last cost
- Primary and alternative suppliers
- Min/max/reorder point
- Strategic legacy flag

### 4.2 Inventory Overview
- Value by company/location/category
- Quantity on hand and allocated
- Available quantity
- Last receipt and last usage
- Age classification

### 4.3 Inventory Locations
- Warehouses
- Production areas
- Service vehicles
- Project sites
- Repair benches
- Donor stock
- Quarantine
- Strategic legacy locations

### 4.4 Transfers
- Search enterprise inventory
- Recommend transfer before buying
- Create transfer
- Ship/receive transfer
- Transfer history

### 4.5 Cycle Counts
- Schedule count
- Count worksheet
- Variance review
- Approval and posting
- Accuracy trend

### 4.6 Aging & Disposition
- Active
- Monitor
- Slow-moving
- Excess
- Obsolete
- Strategic legacy
- Recommended action and owner

---

## 5. Projects & Work Orders

### Project/work-order list
- Customer orders
- Manufacturing orders
- Service work orders
- Construction projects
- Vehicle repairs
- Restorations
- Facility and capital projects

### Project/work-order detail
Tabs:
- Overview
- Material budget
- Purchase commitments
- Material issued
- Returns
- Scrap/rework
- Variance
- Timeline
- Activity

### Key workflow
Budget -> request -> approval -> purchase -> receipt -> issue -> return/scrap -> closeout -> margin review

---

## 6. Savings

### 6.1 Opportunity Pipeline
Stages:
- Identified
- Analyzing
- Negotiating
- Approved
- Implementing
- Completed
- Rejected

### 6.2 Opportunity Detail
- Baseline cost
- Expected annual savings
- Implementation cost
- Net first-year benefit
- Supplier/category/company
- Evidence
- Owner
- Milestones
- Risks
- Accounting validation
- Realized savings

### 6.3 Negotiation Workspace
- Consolidated demand
- Current pricing
- Target pricing
- Terms
- Freight
- Lead time
- Warranty
- Service levels
- Alternatives
- Meeting history

---

## 7. AI Briefing Room

### 7.1 Generate Briefing
Briefing types:
- Weekly executive brief
- Supplier risk brief
- Inventory risk brief
- PO risk brief
- Savings opportunity brief
- Custom question

### 7.2 Findings Queue
Finding types:
- Duplicate supplier
- Price variance
- Late PO
- Stockout risk
- Transfer opportunity
- Excess inventory
- Supplier risk
- Savings opportunity
- Data-quality issue

### 7.3 Finding workflow
New -> Reviewing -> Accepted/Dismissed -> Actioned -> Closed

### 7.4 AI controls
- Source data window
- Company scope
- Prompt version
- Model name
- Confidence score
- Human reviewer
- Publish/archive

### Guardrails
- AI cannot approve purchases, change supplier status, post inventory adjustments or validate savings.
- Every recommendation must show supporting records.
- Human approval is required for all operational and financial actions.

---

## 8. Reports

### Initial reports
- Executive procurement scorecard
- Consolidated spend analysis
- Open and past-due POs
- Supplier performance scorecard
- Inventory accuracy
- Inventory turns and days on hand
- Inventory aging and disposition
- Stockout and expedite costs
- Project/work-order material variance
- Savings and improvement pipeline

### Report features
- Saved filters
- Schedule delivery
- CSV/XLSX/PDF export
- Drill-down
- Comments and assignments
- Snapshot history

---

## 9. Administration

- Companies
- Locations
- Departments
- Users
- Roles and permissions
- Approval thresholds
- Categories
- Units of measure
- Reason codes
- Import mappings
- AI settings
- Notification rules
- Audit log
- System health

---

# MVP release boundaries

## Included in v1
- Authentication and role-based access
- Company-level filtering
- Supplier and item masters
- CSV imports for PO and inventory data
- Open PO and inventory-aging workflows
- Supplier scorecards
- Savings pipeline
- AI briefings using read-only structured data
- Audit logs and Accounting validation

## Deferred
- Full ERP replacement
- Automated supplier payments
- Electronic data interchange
- Autonomous purchasing
- Advanced demand planning
- Mobile warehouse scanning
- Customer-facing supplier portal
- Deep accounting posting integration
