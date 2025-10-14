# Gemini CLI Usage Guide for GVV Project

This document provides comprehensive guidance for using the Gemini CLI tool when working with the GVV codebase, particularly for large codebase analysis that might exceed other AI tools' context limits.

## Overview

The Gemini CLI leverages Google Gemini's massive context window to analyze entire codebases or large collections of files. It's particularly useful for:

- Analyzing large codebases or multiple files that exceed context limits
- Comparing multiple large files
- Understanding project-wide patterns or architecture
- Checking if specific features, patterns, or security measures are implemented
- Verifying the presence of certain coding patterns throughout the codebase

## File and Directory Inclusion Syntax

Use the `@` syntax to include files and directories in your Gemini prompts. Paths should be relative to the LOCATION where you run the gemini command.

### Examples

#### Single file analysis:
```bash
gemini -p "@src/main.py Explain the purpose and structure of this file"
```

#### Multiple files:
```bash
gemini -p "@package.json @src/index.js Analyze the dependencies used in the code"
```

#### Entire directory:
```bash
gemini -p "@src/ Summarize the architecture of this codebase"
```

#### Multiple directories:
```bash
gemini -p "@src/ @tests/ Analyze test coverage for the source code"
```

#### Current directory and subdirectories:
```bash
gemini -p "@./ Give me an overview of this entire project"

# Or use the --all_files flag:
gemini --all_files -p "Analyze the project structure and dependencies"
```

## Implementation Verification Examples

### Check if a feature is implemented:
```bash
gemini -p "@src/ @lib/ Has dark mode been implemented in this codebase? Show me the relevant files and functions"
```

### Check authentication implementation:
```bash
gemini -p "@src/ @middleware/ Is JWT authentication implemented? List all authentication-related endpoints and middleware"
```

### Search for specific patterns:
```bash
gemini -p "@src/ Are there any React hooks that handle WebSocket connections? List them with file paths"
```

### Check error handling:
```bash
gemini -p "@src/ @api/ Is error handling properly implemented for all API endpoints? Show examples of try-catch blocks"
```

### Check rate limiting:
```bash
gemini -p "@backend/ @middleware/ Is rate limiting implemented for the API? Show implementation details"
```

### Check caching strategy:
```bash
gemini -p "@src/ @lib/ @services/ Is Redis caching implemented? List all cache-related functions and their usage"
```

### Check specific security measures:
```bash
gemini -p "@src/ @api/ Are SQL injection protections implemented? Show how user inputs are sanitized"
```

### Check test coverage for features:
```bash
gemini -p "@src/payment/ @tests/ Is the payment processing module fully tested? List all test cases"
```

## GVV-Specific Use Cases

### Analyze GVV Architecture
```bash
gemini -p "@application/ Analyze the GVV CodeIgniter 2.x architecture and explain the MVC pattern implementation"
```

### Check Metadata Implementation
```bash
gemini -p "@application/libraries/Gvvmetadata.php @application/controllers/ How is the metadata-driven system implemented across controllers?"
```

### Verify Database Migrations
```bash
gemini -p "@application/migrations/ @application/config/migration.php List all database migrations and verify the migration version is up to date"
```

### Analyze Testing Coverage
```bash
gemini -p "@application/tests/ What types of tests are implemented and what is the overall testing strategy?"
```

### Check Security Implementation
```bash
gemini -p "@application/controllers/ @application/libraries/ How is authentication and authorization implemented? Show the dx_auth usage patterns"
```

### Review Anonymization System
```bash
gemini -p "@application/controllers/membre.php @application/controllers/vols_decouverte.php @doc/design_notes/anonymization.md Analyze the complete anonymization system implementation"
```

## When to Use Gemini CLI vs Other Tools

### Use Gemini CLI when:
- You're analyzing entire codebases or large directories
- You're comparing multiple large files
- You need to understand project-wide patterns or architecture
- The current context window is insufficient for the task
- You're working with files totaling more than 100KB
- You're checking if specific features, patterns, or security measures are implemented
- You're verifying the presence of certain coding patterns throughout the codebase

### Use other MCP tools when:
- Debugging specific issues
- Exploring particular modules/features
- Iterative code review within Claude
- Following code flow step-by-step

## Best Practices

### 1. Be Specific in Queries
Instead of "analyze this code", use specific questions like:
```bash
gemini -p "@application/ What authentication mechanisms are implemented and how do they integrate with the CodeIgniter framework?"
```

### 2. Focus on Architectural Patterns
```bash
gemini -p "@application/ @doc/ How does the metadata-driven system work and which controllers implement it correctly?"
```

### 3. Verify Implementation Completeness
```bash
gemini -p "@application/controllers/ @application/tests/ Which controllers have corresponding test files and which are missing tests?"
```

### 4. Check Cross-File Dependencies
```bash
gemini -p "@application/ Map the dependencies between models, controllers, and libraries in the GVV system"
```

### 5. Security Audits
```bash
gemini -p "@application/ Identify all places where user input is processed and verify proper sanitization is implemented"
```

## Common Patterns for GVV Analysis

### Check CodeIgniter 2.x Compliance
```bash
gemini -p "@application/controllers/ @application/models/ Are all controllers and models following CodeIgniter 2.x conventions and best practices?"
```

### Verify Multi-language Support
```bash
gemini -p "@application/language/ @application/controllers/ How complete is the multi-language support (French, English, Dutch) across the application?"
```

### Analyze Database Operations
```bash
gemini -p "@application/models/ @application/migrations/ Review all database operations and check if they properly use the Common_Model pattern"
```

### Check Bootstrap 5 Integration
```bash
gemini -p "@application/views/ @themes/ @assets/ How consistently is Bootstrap 5 implemented across the UI components?"
```

## Integration with Development Workflow

### Pre-Development Analysis
Use Gemini CLI to understand existing patterns before adding new features:
```bash
gemini -p "@application/ Before adding a new controller for [feature], show me the established patterns for similar controllers"
```

### Post-Development Review
Verify your changes fit the established patterns:
```bash
gemini -p "@application/controllers/new_controller.php @application/controllers/ Does this new controller follow the same patterns as existing controllers?"
```

### Documentation Verification
Check if documentation matches implementation:
```bash
gemini -p "@doc/ @application/ Does the current documentation accurately reflect the implemented architecture and features?"
```

## Tips for Effective Usage

1. **Start Broad, Then Narrow**: Begin with directory-level analysis, then focus on specific files
2. **Use Context**: Reference related documentation files in your queries
3. **Ask Comparative Questions**: "How does X compare to Y in this codebase?"
4. **Verify Claims**: "Is feature X actually implemented as described in the documentation?"
5. **Check Consistency**: "Are all controllers implementing error handling the same way?"

## Limitations and Considerations

- **Processing Time**: Large codebase analysis may take longer than single-file operations
- **Context Retention**: Each gemini command is independent; no conversation history
- **File Size Limits**: While large, there are still practical limits to context size
- **Network Dependency**: Requires internet connection and Google API access

## Troubleshooting

### If analysis seems incomplete:
- Break down into smaller directory chunks
- Focus on specific file types or patterns
- Use multiple targeted queries instead of one broad query

### If responses are too general:
- Add more specific requirements to your prompt
- Include examples of what you're looking for
- Reference specific files or patterns you want compared

### If you hit context limits:
- Analyze subdirectories separately
- Focus on specific file types (controllers only, models only, etc.)
- Use exclude patterns to filter out unnecessary files