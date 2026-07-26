# Section 11 — Supplier Comparison & Strategic Sourcing

Open `/app/suppliers.php` and choose **Compare Suppliers** to enter the strategic sourcing workspace.

The workspace compares two to five suppliers using current scoped supplier, scorecard, purchase-order, contract, inventory, payment-term, risk, and company-coverage evidence. Users with supplier-edit permission can save governed decisions; users with approval-submit permission can route them into Reviews & Approvals.

Production decision persistence requires the deferred migration:

`database/20260726_section11_supplier_comparison.sql`

The comparison and CSV export remain available before migration, but Production Data saves are intentionally disabled until the table exists.
