# GitHub Copilot Prompt Guidelines for GVV Project

## Overview

This document provides guidelines for crafting effective prompts when using GitHub Copilot to work on the GVV project. It complements the existing [GVV Copilot Instructions](../../.github/copilot-instructions.md) which already covers the technical context, architecture, and development guidelines.

## Prompt Structure Best Practices

### Reference Existing Context
Instead of repeating technical details, reference the existing documentation:
```
"Context: GVV project (see .github/copilot-instructions.md for technical details)"
"Following GVV's CodeIgniter 2.x patterns as documented"
"Using established GVV development guidelines"
```

### Focus on the Specific Request
Since the technical context is already established, focus your prompts on:
- **Specific problem or feature** you're addressing
- **Exact requirements** and constraints
- **Expected outcome** and success criteria
- **Integration points** with existing GVV components

### Recommended points
- **Re-use existing code** as much as possible instead of creating new one.
- **Ask for clarification** if you are not sure, stop and ask for clarification
- **Work in an incremental way** and stop after each step for review

## Prompt Templates by Use Case

### Design Notes & Architecture

## Optimized Prompt Templates

Since GVV's technical context is documented in `.github/copilot-instructions.md`, these templates focus on the request-specific elements:

### General prompt recommendations

**Streamlined Template:**
```
Context: GVV project (see copilot-instructions.md)
Goals: [Specific component/feature to design]
Current State: [What exists now that relates to this]
Requirements: [Specific functional needs]
Integration: [How it connects to existing GVV systems]
Constraints: [Any specific limitations beyond standard GVV constraints]
Output: [What format you need - diagram, code structure, etc.]
```

### Design Notes & Architecture

**Streamlined Template:**
```
Context: GVV project (see copilot-instructions.md)
Design Goal: [Specific component/feature to design]
Current State: [What exists now that relates to this]
Requirements: [Specific functional needs]
Integration: [How it connects to existing GVV systems]
Constraints: [Any specific limitations beyond standard GVV constraints]
Output: [What format you need - diagram, code structure, etc.]
```

**Example:**
```
Context: GVV project (see copilot-instructions.md)
Design Goal: Add instructor certification tracking to member management
Current State: Existing membres table and member management system
Requirements:
- Track multiple certifications per instructor (type, dates, authority)
- Validate certifications during flight booking
- Support certification expiry notifications
Integration: Integrate with existing membre controller and flight booking system
Constraints: Must work with current membre table structure or provide migration
Output: Database schema changes and controller modifications
```

### Bug Fixes

**Streamlined Template:**
```
Context: GVV project (see copilot-instructions.md)
Problem: [Specific issue description]
Location: [File/method/line where issue occurs]
Expected vs Actual: [What should happen vs what happens]
Code Context: [Relevant code snippets]
Fix Requirements: [Specific constraints for the solution]
Testing: [How to verify the fix works]
```

**Example:**
```
Context: GVV project (see copilot-instructions.md)
Problem: Race condition in multi-tab editing causes data corruption
Location: application/libraries/Gvv_Controller.php, formValidation() method ~line 567
Expected vs Actual: Each tab should edit its own record, but session storage causes last-opened ID to be used for all submissions
Code Context: 
- edit(): $this->session->set_userdata('initial_id', $id)
- formValidation(): $initial_id = $this->session->userdata('initial_id')
Fix Requirements: Maintain backward compatibility, follow CI 2.x patterns, support gradual migration
Testing: Open different records in multiple tabs, verify each updates correctly
```

```
Context: GVV project (see copilot-instructions.md)
Problem: Some users experiment unwanted disconnections. There is no obvious pattern like poor Internet connection, specific browser or operating systems that could explain it. Some others users are almost never experimenting this problem. 

Expected vs Actual: Users should stay connected until they logout or the 2 hours time out.
Testing: As it is not really reproductible, analyze the code and provide advices.
Generate: a document doc/bugs/disconnection_issue.md with your analyse and advices.
```

### New Features

### New Features

**Streamlined Template:**
```
Context: GVV project (see copilot-instructions.md)
Feature: [Clear feature name]
User Story: As a [role], I want [functionality] so that [benefit]
Requirements: [Specific functional and technical needs]
Integration: [How it connects to existing GVV components]
Special Constraints: [Any constraints beyond standard GVV guidelines]
Testing: [Success criteria and validation approach]
```

**Example:**
```
Context: GVV project (see copilot-instructions.md)
Feature: Email notifications for flight scheduling
User Story: As a flight instructor, I want automatic emails when students book/modify lessons so I can prepare accordingly
Requirements:
- Send emails on flight create/modify/cancel
- Include flight details and weather info
- User-configurable notification preferences
- Support existing multi-language system
Integration: Extend existing email system, integrate with flight booking controllers
Special Constraints: Must comply with GDPR for email preferences
Testing: Verify emails sent correctly, test preference management, check multi-language support
```

### Code Refactoring

**Streamlined Template:**
```
Context: GVV project (see copilot-instructions.md)
Refactoring Goal: [What needs improvement]
Current Issues: [Problems with existing code]
Desired Outcome: [What the improved code should achieve]
Migration Strategy: [How to implement safely without breaking existing functionality]
Success Criteria: [How to measure improvement]
```

### Code Review

**Template:**
```
Context: GVV project (see copilot-instructions.md)
Review: The Rapprochement feature, its controller, model, library and views
Review goals: identify bugs, potential bugs, inefficient implementations, poor style, high complexity, code duplications
Current Issues: [Problems with existing code]
Desired Outcome: A synthetic md file, organized by remark criticity, with exact location where problem occur
Constraints: No code modification, include the date in the review result
Method: if required you may process by steps, and produce separate reports for bug and potential bugs, poor style and code duplication
Success Criteria: The quality of you analysis will be evaluated by humans
```

## Advanced Prompt Techniques

### 1. Reference Existing Context
```
"Following GVV patterns documented in copilot-instructions.md"
"Using established GVV controller/model/view patterns"
"Applying GVV's legacy-aware development approach"
```

### 2. Multi-Step Analysis
```
"Analyze current GVV implementation, identify issues, propose solutions following project guidelines"
```

### 3. Specific Integration Points
```
"Integrate with existing [specific GVV component], following established patterns"
"Extend [existing GVV class/system] without breaking current functionality"
```


## Optimized Examples

### Efficient Bug Fix Prompt
```
Context: GVV project (see copilot-instructions.md)
Problem: DX_Auth sessions expire after 30 minutes instead of configured 2 hours
Location: application/libraries/DX_Auth.php 
Current Behavior: Users logged out unexpectedly during long operations
Fix Requirements: Honor configuration setting, maintain existing auth flow
Testing: Set 2-hour timeout, verify persistence across browser refresh
```

### Efficient Feature Request
```
Context: GVV project (see copilot-instructions.md)
Feature: Bulk member import from CSV
User Story: As admin, I want to import member lists to reduce manual data entry
Requirements: 
- Parse CSV with member fields (name, email, license, etc.)
- Validate data using existing member validation rules
- Preview before import with error reporting
- Use existing member controller patterns
Integration: Extend existing membre controller, reuse validation logic
Testing: Test with valid/invalid CSV files, verify all member fields imported correctly
```

### Efficient Refactoring Prompt
```
Context: GVV project (see copilot-instructions.md)
Refactoring Goal: Extract common date validation logic from multiple controllers
Current Issues: Date validation duplicated in vols_planeur, vols_avion, membre controllers
Desired Outcome: Single date validation helper, consistent error messages
Migration Strategy: Create helper, update controllers one by one, maintain exact behavior
Success Criteria: All existing forms work identically, validation logic centralized
```

## Key Principles for Efficient Prompts

1. **Reference, Don't Repeat**: Point to existing documentation instead of restating context
2. **Be Specific**: Focus on the unique aspects of your request
3. **Show Integration**: Explain how it fits with existing GVV components
4. **Consider Migration**: Always think about backward compatibility
5. **Define Success**: Make it clear how to verify the solution works

## Workflow Recommendations for GitHub Copilot

### Work in Small Steps
Break complex changes into smaller, manageable pieces:
```
❌ "Refactor the entire member management system"
✅ "Step 1: Extract member validation logic into a helper class"
✅ "Step 2: Update membre controller to use the new validation helper"
✅ "Step 3: Update other controllers one by one"
```

### Request Clarification When in Doubt
If requirements are unclear, ask Copilot to clarify before proceeding:
```
"Before implementing this feature, please clarify:
- Should this work with existing authentication or require new permissions?
- How should this integrate with the current member registration flow?
- What happens to existing data during this migration?"
```

### Multi-Step Process Management
For complex implementations, describe the full process but implement incrementally:
```
Context: GVV project (see copilot-instructions.md)
Goal: Implement comprehensive flight instructor certification system
Full Process Overview:
1. Design database schema for certifications
2. Create migration scripts
3. Update member model with certification methods
4. Add certification management to member controller
5. Create certification validation for flight booking
6. Add expiry notification system
7. Update UI for certification management

Request: Please start with step 1 (database schema design) and stop for review before proceeding to implementation.
```

### Incremental Implementation Pattern
```
Context: GVV project (see copilot-instructions.md)
Task: [Describe full scope]
Implementation Plan: [List all steps]
Current Step: Please implement step [X] only and provide:
- Code for this step
- Validation/testing approach for this step
- Summary of what the next step will involve
- Any potential issues or dependencies identified

Stop here for review before proceeding to the next step.
```

## Example: Step-by-Step Implementation

### Complex Feature with Incremental Approach
```
Context: GVV project (see copilot-instructions.md)
Goal: Add automated backup system for flight data
Full Implementation Plan:
1. Design backup configuration system
2. Create backup service class
3. Add scheduling mechanism
4. Implement backup storage (local/remote)
5. Add backup restore functionality
6. Create admin interface for backup management
7. Add monitoring and notification system

Current Request: Please implement ONLY step 1 (backup configuration system):
- Design configuration structure for backup settings
- Create config file template
- Show how settings will be accessed by other components
- Identify any GVV-specific considerations for configuration

Please stop after step 1 for review and confirmation before proceeding.
```


This approach creates more focused, actionable prompts while leveraging the comprehensive context already established in the GVV Copilot Instructions.
