# AI Agent Specification
## Gruber AI Procurement Intelligence System

## Design principle

AI is a supervised analysis and recommendation layer. It may read approved business data, calculate and summarize exceptions, create draft findings, and recommend actions. It may not independently approve, order, receive, adjust, dispose, pay, or validate savings.

## Agent 1: Executive Brief Agent

### Objective
Produce a weekly enterprise or company-level summary of material changes.

### Inputs
- Spend by company/category/supplier
- Open and past-due POs
- Inventory value and aging
- Expedite freight
- Supplier scorecards
- Savings pipeline
- Critical AI findings

### Output
- Executive summary
- Top five risks
- Top five opportunities
- Required decisions
- Company-specific highlights
- Changes since prior briefing

### Human control
Procurement lead reviews before publication.

---

## Agent 2: Supplier Consolidation Agent

### Objective
Identify duplicate suppliers and categories where enterprise leverage may improve pricing or terms.

### Detection logic
- Similar supplier names, domains, addresses and tax identifiers
- Same item or manufacturer part purchased from multiple suppliers
- Same category spread across many suppliers
- Price differences after adjusting for quantity, freight and specification
- Different payment or warranty terms among companies

### Output
- Consolidation candidate
- Estimated combined annual spend
- Affected companies
- Current suppliers
- Pricing and terms comparison
- Recommended negotiation approach

---

## Agent 3: Price Variance Watcher

### Objective
Flag unusual price movement and contract noncompliance.

### Detection logic
- Actual vs contract cost
- Actual vs standard cost
- Current vs prior purchase
- Same item across businesses
- Freight-adjusted landed cost
- Quantity break changes

### Guardrail
Do not call a difference "savings" until Accounting validates the baseline and volume/specification adjustments.

---

## Agent 4: Inventory Transfer Recommender

### Objective
Recommend internal transfers before a new purchase is placed.

### Inputs
- Requested item and required date
- Available enterprise inventory
- Allocations
- Strategic legacy restrictions
- Location and transfer time
- Upcoming demand

### Output
- Source location
- Available quantity
- Receiving company/location
- Transfer cost and timing
- New-purchase cost avoided
- Operational risk

### Guardrail
Strategic legacy, customer-owned, quarantined and allocated inventory cannot be recommended without explicit rules.

---

## Agent 5: Stockout Risk Agent

### Objective
Predict near-term shortages that may delay customers, projects, repairs or production.

### Inputs
- Inventory availability
- Open demand
- Open POs and expected dates
- Lead times
- Usage velocity
- Min/max/reorder controls
- Criticality classification

### Output
- Item and location
- Days until shortage
- Affected demand
- Recommended action
- Supplier or transfer alternatives

---

## Agent 6: PO Risk Agent

### Objective
Rank purchase orders by business impact rather than only days late.

### Factors
- Past-due days
- Supplier acknowledgement
- Customer/project/work-order dependency
- Item criticality
- Available substitutes
- Inventory elsewhere
- Supplier performance history
- Expedite cost

### Output
- Risk score
- Impact statement
- Escalation owner
- Recommended next step

---

## Agent 7: Excess Inventory Agent

### Objective
Separate actionable excess from legitimate strategic legacy inventory.

### Actions suggested
- Use on existing jobs
- Transfer between companies
- Return to supplier
- Sell online
- Bundle
- Refurbish
- Harvest for parts
- Discount/liquidate
- Recycle
- Retain strategically

### Required evidence
- Age
- Quantity and value
- Last usage
- Future demand
- Alternative uses
- Service/legacy criticality

---

## Agent 8: Savings Opportunity Agent

### Objective
Create draft savings opportunities from validated patterns.

### Opportunity types
- Price negotiation
- Supplier consolidation
- Freight reduction
- Payment terms
- Inventory reduction
- Internal transfer
- Process improvement
- Quality improvement
- Warranty recovery
- Specification standardization

### Output
- Baseline definition
- Estimated annual value
- Assumptions
- Required owner
- Implementation cost
- Risks
- Accounting validation status

---

## Data and security requirements

- Use least-privilege database views for AI access.
- Exclude passwords, bank information, tax identifiers and secrets.
- Store prompt version, model name, source snapshot and generated result.
- Retain evidence links for every finding.
- Record reviewer, disposition and action history.
- Support a company-level data boundary and enterprise-level permissions.
- Do not train external models on company data unless contractually approved.

## Initial AI release sequence

1. Weekly executive summaries
2. Duplicate supplier detection
3. Price variance and contract checks
4. Past-due PO risk ranking
5. Inventory transfer recommendations
6. Excess inventory recommendations
7. Savings pipeline drafting
8. Forecasting and predictive stockout models
